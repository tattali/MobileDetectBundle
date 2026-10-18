<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MobileDetectBundle\EventListener;

use MobileDetectBundle\DependencyInjection\Configuration;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Helper\RedirectResponseWithCookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 * @author HenriVesala <henri.vesala@gmail.com>
 *
 * @phpstan-import-type RedirectConfig from Configuration
 * @phpstan-import-type ViewRedirectConfig from Configuration
 */
class RequestResponseListener
{
    public const REDIRECT = 'redirect';
    public const NO_REDIRECT = 'no_redirect';
    public const REDIRECT_WITHOUT_PATH = 'redirect_without_path';

    public const MOBILE = 'mobile';
    public const TABLET = 'tablet';
    public const FULL = 'full';

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var MobileDetectorInterface
     */
    protected $mobileDetector;

    /**
     * @var DeviceView
     */
    protected $deviceView;

    /**
     * @var RedirectConfig
     */
    protected $redirectConf;

    /**
     * @var bool
     */
    protected $isFullPath;

    /**
     * @var bool
     */
    protected $needModifyResponse = false;

    /**
     * @var (\Closure(DeviceView, ResponseEvent): Response)|null
     */
    protected $modifyResponseClosure;

    /**
     * @param RedirectConfig $redirectConf
     */
    public function __construct(
        MobileDetectorInterface $mobileDetector,
        DeviceView $deviceView,
        RouterInterface $router,
        array $redirectConf,
        bool $fullPath = true
    ) {
        $this->mobileDetector = $mobileDetector;
        $this->deviceView = $deviceView;
        $this->router = $router;

        // Configs mobile & tablet
        $this->redirectConf = $redirectConf;
        $this->isFullPath = $fullPath;
    }

    public function handleRequest(RequestEvent $event): void
    {
        // only handle master request, do not handle sub request like esi includes
        // If the device view is "not the mobile view" (e.g. we're not in the request context)
        if ((\defined('Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST') ? \constant('Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST') : \constant('Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST')) !== $event->getRequestType() || $this->deviceView->isNotMobileView()) {
            return;
        }

        $request = $event->getRequest();
        $this->mobileDetector->setUserAgent($request->headers->get('user-agent'));

        // Sets the flag for the response handled by the GET switch param and the type of the view.
        if ($this->deviceView->hasSwitchParam()) {
            $event->setResponse($this->getRedirectResponseBySwitchParam($request));

            return;
        }

        // If neither the SwitchParam nor the cookie are set, detect the view...
        $cookieIsSet = null !== $this->deviceView->getRequestedViewType();
        if (!$cookieIsSet) {
            if (false === $this->redirectConf['detect_tablet_as_mobile'] && $this->mobileDetector->isTablet()) {
                $this->deviceView->setTabletView();
            } elseif ($this->mobileDetector->isMobile()) {
                $this->deviceView->setMobileView();
            } else {
                $this->deviceView->setFullView();
            }
        }

        $viewType = $this->deviceView->getViewType();
        // Check if we must redirect to the target view and do so if needed
        if (null !== $viewType && $this->mustRedirect($request, $viewType)) {
            $response = $this->getRedirectResponse($request, $viewType);
            if (null !== $response) {
                $event->setResponse($response);
            }

            return;
        }

        // No need to redirect

        // We don't need to modify _every_ response: once the cookie is set,
        // save bandwith and CPU cycles by just letting it expire someday.
        if ($cookieIsSet) {
            return;
        }

        // Sets the flag for the response handler and prepares the modification closure
        if (null !== $viewType) {
            $this->needModifyResponse = true;
            $this->prepareResponseModification($viewType);
        }
    }

    /**
     * Will this request listener modify the response? This flag will be set during the "handleRequest" phase.
     * Made public for testability.
     */
    public function needsResponseModification(): bool
    {
        return $this->needModifyResponse;
    }

    public function handleResponse(ResponseEvent $event): void
    {
        if ($this->needModifyResponse && $this->modifyResponseClosure instanceof \Closure) {
            $modifyClosure = $this->modifyResponseClosure;
            $event->setResponse($modifyClosure($this->deviceView, $event));

            return;
        }
    }

    protected function getRedirectResponseBySwitchParam(Request $request): RedirectResponseWithCookie
    {
        $viewType = $this->deviceView->getViewType();
        $redirectUrl = null;
        if (null !== $viewType && $this->mustRedirect($request, $viewType)) {
            // Avoid unnecessary redirects: if we need to redirect to another view,
            // do it in one response while setting the cookie.
            $redirectUrl = $this->getRedirectUrl($request, $viewType);
        }

        if (null === $redirectUrl) {
            if (true === $this->isFullPath) {
                $redirectUrl = $request->getUriForPath($request->getPathInfo());
                // $redirectUrl = ($request->getPathInfo()) ? $request->getUriForPath($request->getPathInfo()) : $this->getCurrentHost($request);
                $queryParams = $request->query->all();
                if (\array_key_exists($this->deviceView->getSwitchParam(), $queryParams)) {
                    unset($queryParams[$this->deviceView->getSwitchParam()]);
                }
                if (\count($queryParams) > 0) {
                    $redirectUrl .= '?'.Request::normalizeQueryString(http_build_query($queryParams, '', '&'));
                }
            } else {
                $redirectUrl = $request->getSchemeAndHttpHost();
            }
        }

        return $this->deviceView->getRedirectResponseBySwitchParam($redirectUrl);
    }

    /**
     * Do we have to redirect?
     *
     * @param string $viewType The view we want to redirect to
     */
    protected function mustRedirect(Request $request, string $viewType): bool
    {
        $viewConfig = $this->getViewRedirectConf($viewType);
        if (null === $viewConfig
            || !$viewConfig['is_enabled']
            || (self::NO_REDIRECT === $this->getRoutingOption($this->getRouteName($request), $viewType))
        ) {
            return false;
        }

        return $request->getSchemeAndHttpHost() !== $viewConfig['host'];
    }

    protected function getRoutingOption(string $routeName, string $optionName): ?string
    {
        $option = null;
        $route = $this->router->getRouteCollection()->get($routeName);

        if ($route instanceof Route) {
            $option = $route->getOption($optionName);
        }

        $viewConfig = $this->getViewRedirectConf($optionName);
        if (false === (bool) $option && null !== $viewConfig) {
            $option = $viewConfig['action'];
        }

        if (\in_array($option, [self::REDIRECT, self::REDIRECT_WITHOUT_PATH, self::NO_REDIRECT], true)) {
            return $option;
        }

        return null;
    }

    protected function getRedirectUrl(Request $request, string $view): ?string
    {
        $viewConfig = $this->getViewRedirectConf($view);
        $routingOption = $this->getRoutingOption($this->getRouteName($request), $view);
        if (null !== $viewConfig && null !== $routingOption) {
            if (self::REDIRECT === $routingOption) {
                if (null === $viewConfig['host']) {
                    return null;
                }

                // Make sure to hint at the device override, otherwise infinite loop
                // redirection may occur if different device views are hosted on
                // different domains (since the cookie can't be shared across domains)
                $queryParams = $request->query->all();
                $queryParams[$this->deviceView->getSwitchParam()] = $view;

                return rtrim($viewConfig['host'], '/').$request->getPathInfo().'?'.Request::normalizeQueryString(http_build_query($queryParams));
            }
            if (self::REDIRECT_WITHOUT_PATH === $routingOption) {
                // Make sure to hint at the device override, otherwise infinite loop
                // redirections may occur if different device views are hosted on
                // different domains (since the cookie can't be shared across domains)
                return $viewConfig['host'].'?'.$this->deviceView->getSwitchParam().'='.$view;
            }

            return null;
        }

        return null;
    }

    /**
     * Gets the RedirectResponse for the specified view.
     *
     * @param string $view the view for which we want the RedirectResponse
     */
    protected function getRedirectResponse(Request $request, string $view): ?RedirectResponse
    {
        $viewConfig = $this->getViewRedirectConf($view);
        $host = $this->getRedirectUrl($request, $view);
        if (null !== $viewConfig && null !== $host) {
            return $this->deviceView->getRedirectResponse(
                $view,
                $host,
                $viewConfig['status_code']
            );
        }

        return null;
    }

    private function getRouteName(Request $request): string
    {
        $routeName = $request->get('_route');

        return \is_string($routeName) ? $routeName : '';
    }

    /**
     * Gets the redirect configuration of a view, or null when the name is not a view.
     *
     * @return ViewRedirectConfig|null
     */
    private function getViewRedirectConf(string $view): ?array
    {
        $viewConfig = $this->redirectConf[$view] ?? null;

        return \is_array($viewConfig) ? $viewConfig : null;
    }

    /**
     * Prepares the response modification which will take place after the controller logic has been executed.
     *
     * @param string $view the view for which to prepare the response modification
     */
    protected function prepareResponseModification(string $view): void
    {
        $this->modifyResponseClosure = function (DeviceView $deviceView, ResponseEvent $event) use ($view) {
            return $deviceView->modifyResponse($view, $event->getResponse());
        };
    }
}
