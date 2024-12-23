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

use Detection\MobileDetect;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Helper\RedirectResponseWithCookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 * @author HenriVesala <henri.vesala@gmail.com>
 */
class RequestResponseListener
{
    public const REDIRECT = 'redirect';
    public const NO_REDIRECT = 'no_redirect';
    public const REDIRECT_WITHOUT_PATH = 'redirect_without_path';

    public const MOBILE = 'mobile';
    public const TABLET = 'tablet';
    public const DESKTOP = 'desktop';

    protected bool $needModifyResponse = false;

    protected ?\Closure $modifyResponseClosure;

    /**
     * @param array<string, mixed> $redirectConf
     */
    public function __construct(
        protected readonly MobileDetect $mobileDetect,
        protected readonly DeviceView $deviceView,
        protected readonly RouterInterface $router,
        protected readonly array $redirectConf,
        protected readonly bool $isDesktopPath = true,
    ) {
    }

    public function handleRequest(RequestEvent $event): void
    {
        // Only handle main request, do not handle sub request like esi includes
        // If the device view is "not the mobile view" (e.g. we're not in the request context)
        if (!$event->isMainRequest() || $this->deviceView->isNotMobileView()) {
            return;
        }

        $request = $event->getRequest();
        $this->mobileDetect->setUserAgent($request->headers->get('user-agent', ''));

        // Sets the flag for the response handled by the GET switch param and the type of the view.
        if ($this->deviceView->hasSwitchParam()) {
            $event->setResponse($this->getRedirectResponseBySwitchParam($request));

            return;
        }

        // If neither the SwitchParam nor the cookie are set, detect the view...
        $cookieIsSet = null !== $this->deviceView->getRequestedViewType();
        if (!$cookieIsSet) {
            if (false === $this->redirectConf['detect_tablet_as_mobile'] && $this->mobileDetect->isTablet()) {
                $this->deviceView->setTabletView();
            } elseif ($this->mobileDetect->isMobile()) {
                $this->deviceView->setMobileView();
            } else {
                $this->deviceView->setDesktopView();
            }
        }

        $viewType = $this->deviceView->getViewType();
        // Check if we must redirect to the target view and do so if needed
        if ($viewType && $this->mustRedirect($request, $viewType)) {
            if ($response = $this->getRedirectResponse($request, $viewType)) {
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
        $this->needModifyResponse = true;
        $this->prepareResponseModification($this->deviceView->getViewType());
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
        if ($this->mustRedirect($request, $this->deviceView->getViewType())) {
            // Avoid unnecessary redirects: if we need to redirect to another view,
            // do it in one response while setting the cookie.
            $redirectUrl = $this->getRedirectUrl($request, $this->deviceView->getViewType());
        } else {
            if (true === $this->isDesktopPath) {
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
        if (!isset($this->redirectConf[$viewType])
            || !$this->redirectConf[$viewType]['is_enabled']
            || (self::NO_REDIRECT === $this->getRoutingOption($request->get('_route'), $viewType))
        ) {
            return false;
        }

        return $request->getSchemeAndHttpHost() !== $this->redirectConf[$viewType]['host'];
    }

    protected function getRoutingOption(string $routeName, string $optionName): ?string
    {
        $option = null;
        $route = $this->router->getRouteCollection()->get($routeName);

        if ($route instanceof Route) {
            $option = $route->getOption($optionName);
        }

        if (!$option && isset($this->redirectConf[$optionName])) {
            $option = $this->redirectConf[$optionName]['action'];
        }

        if (\in_array($option, [self::REDIRECT, self::REDIRECT_WITHOUT_PATH, self::NO_REDIRECT], true)) {
            return $option;
        }

        return null;
    }

    protected function getRedirectUrl(Request $request, string $view): ?string
    {
        if ($routingOption = $this->getRoutingOption($request->get('_route'), $view)) {
            if (self::REDIRECT === $routingOption) {
                // Make sure to hint at the device override, otherwise infinite loop
                // redirection may occur if different device views are hosted on
                // different domains (since the cookie can't be shared across domains)
                $queryParams = $request->query->all();
                $queryParams[$this->deviceView->getSwitchParam()] = $view;

                return rtrim($this->redirectConf[$view]['host'], '/').$request->getPathInfo().'?'.Request::normalizeQueryString(http_build_query($queryParams));
            }
            if (self::REDIRECT_WITHOUT_PATH === $routingOption) {
                // Make sure to hint at the device override, otherwise infinite loop
                // redirections may occur if different device views are hosted on
                // different domains (since the cookie can't be shared across domains)
                return $this->redirectConf[$view]['host'].'?'.$this->deviceView->getSwitchParam().'='.$view;
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
        if ($host = $this->getRedirectUrl($request, $view)) {
            return $this->deviceView->getRedirectResponse(
                $view,
                $host,
                $this->redirectConf[$view]['status_code'],
            );
        }

        return null;
    }

    /**
     * Prepares the response modification which will take place after the controller logic has been executed.
     *
     * @param string $view the view for which to prepare the response modification
     */
    protected function prepareResponseModification(string $view): void
    {
        $this->modifyResponseClosure = static fn (DeviceView $deviceView, ResponseEvent $event) => $deviceView->modifyResponse($view, $event->getResponse());
    }
}
