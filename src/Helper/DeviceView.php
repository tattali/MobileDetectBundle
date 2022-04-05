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

namespace MobileDetectBundle\Helper;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 */
class DeviceView
{
    public const VIEW_MOBILE = 'mobile';
    public const VIEW_TABLET = 'tablet';
    public const VIEW_FULL = 'full';
    public const VIEW_NOT_MOBILE = 'not_mobile';

    public const COOKIE_KEY_DEFAULT = 'device_view';
    public const COOKIE_PATH_DEFAULT = '/';
    public const COOKIE_DOMAIN_DEFAULT = '';
    public const COOKIE_SECURE_DEFAULT = false;
    public const COOKIE_HTTP_ONLY_DEFAULT = true;
    public const COOKIE_RAW_DEFAULT = false;
    public const COOKIE_SAMESITE_DEFAULT = Cookie::SAMESITE_LAX;
    public const COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT = '1 month';
    public const SWITCH_PARAM_DEFAULT = 'device_view';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $requestedViewType;

    /**
     * @var string
     */
    protected $viewType;

    /**
     * @var string
     */
    protected $cookieKey = self::COOKIE_KEY_DEFAULT;

    /**
     * @var string
     */
    protected $cookiePath = self::COOKIE_PATH_DEFAULT;

    /**
     * @var string
     */
    protected $cookieDomain = self::COOKIE_DOMAIN_DEFAULT;

    /**
     * @var bool
     */
    protected $cookieSecure = self::COOKIE_SECURE_DEFAULT;

    /**
     * @var bool
     */
    protected $cookieHttpOnly = self::COOKIE_HTTP_ONLY_DEFAULT;

    /**
     * @var bool
     */
    protected $cookieRaw = self::COOKIE_RAW_DEFAULT;

    /**
     * @var string|null
     */
    protected $cookieSameSite = self::COOKIE_SAMESITE_DEFAULT;

    /**
     * @var string
     */
    protected $cookieExpireDatetimeModifier = self::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT;

    /**
     * @var string
     */
    protected $switchParam = self::SWITCH_PARAM_DEFAULT;

    /**
     * @var array
     */
    protected $redirectConfig = [];

    public function __construct(RequestStack $requestStack = null)
    {
        $methodName = method_exists(RequestStack::class, 'getMainRequest') ? 'getMainRequest' : 'getMasterRequest';
        if (!$requestStack || !$this->request = $requestStack->{$methodName}()) {
            $this->viewType = self::VIEW_NOT_MOBILE;

            return;
        }

        if ($this->request->query->has($this->switchParam)) {
            $this->viewType = $this->request->query->get($this->switchParam);
        } elseif ($this->request->cookies->has($this->cookieKey)) {
            $this->viewType = $this->request->cookies->get($this->cookieKey);
        }

        $this->requestedViewType = $this->viewType;
    }

    /**
     * Gets the view type for a device.
     */
    public function getViewType()
    {
        return $this->viewType;
    }

    /**
     * Gets the view type that has explicitly been requested either by switch param, or by cookie.
     *
     * @return string the requested view type or null if no view type has been explicitly requested
     */
    public function getRequestedViewType()
    {
        return $this->requestedViewType;
    }

    /**
     * Is the device in full view.
     */
    public function isFullView()
    {
        return self::VIEW_FULL === $this->viewType;
    }

    public function isTabletView()
    {
        return self::VIEW_TABLET === $this->viewType;
    }

    public function isMobileView()
    {
        return self::VIEW_MOBILE === $this->viewType;
    }

    /**
     * Is not the device a mobile view type (PC, Mac, etc.).
     */
    public function isNotMobileView()
    {
        return self::VIEW_NOT_MOBILE === $this->viewType;
    }

    /**
     * Has the Request the switch param in the query string (GET header).
     */
    public function hasSwitchParam()
    {
        return $this->request && $this->request->query->has($this->switchParam);
    }

    public function setView(string $view)
    {
        $this->viewType = $view;
    }

    /**
     * Sets the full (desktop) view type.
     */
    public function setFullView()
    {
        $this->viewType = self::VIEW_FULL;
    }

    public function setTabletView()
    {
        $this->viewType = self::VIEW_TABLET;
    }

    public function setMobileView()
    {
        $this->viewType = self::VIEW_MOBILE;
    }

    public function setNotMobileView()
    {
        $this->viewType = self::VIEW_NOT_MOBILE;
    }

    public function getRedirectConfig()
    {
        return $this->redirectConfig;
    }

    public function setRedirectConfig(array $redirectConfig)
    {
        $this->redirectConfig = $redirectConfig;
    }

    public function getRedirectResponseBySwitchParam(string $redirectUrl)
    {
        switch ($this->getSwitchParamValue()) {
            case self::VIEW_MOBILE:
                $viewType = self::VIEW_MOBILE;
                break;
            case self::VIEW_TABLET:
                $viewType = self::VIEW_TABLET;

                if (isset($this->getRedirectConfig()['detect_tablet_as_mobile']) && true === $this->getRedirectConfig()['detect_tablet_as_mobile']) {
                    $viewType = self::VIEW_MOBILE;
                }
                break;
            default:
                $viewType = self::VIEW_FULL;
        }

        return new RedirectResponseWithCookie(
            $redirectUrl,
            $this->getStatusCode($viewType),
            $this->createCookie($viewType)
        );
    }

    /**
     * Gets the switch param value from the query string (GET header).
     */
    public function getSwitchParamValue()
    {
        if (!$this->request) {
            return null;
        }

        return $this->request->query->get($this->switchParam, self::VIEW_FULL);
    }

    public function getCookieExpireDatetimeModifier()
    {
        return $this->cookieExpireDatetimeModifier;
    }

    public function setCookieExpireDatetimeModifier(string $cookieExpireDatetimeModifier)
    {
        $this->cookieExpireDatetimeModifier = $cookieExpireDatetimeModifier;
    }

    public function getCookieKey()
    {
        return $this->cookieKey;
    }

    public function setCookieKey(string $cookieKey)
    {
        $this->cookieKey = $cookieKey;
    }

    public function getCookiePath()
    {
        return $this->cookiePath;
    }

    public function setCookiePath(string $cookiePath)
    {
        $this->cookiePath = $cookiePath;
    }

    public function getCookieDomain()
    {
        return $this->cookieDomain;
    }

    public function setCookieDomain(string $cookieDomain)
    {
        $this->cookieDomain = $cookieDomain;
    }

    public function isCookieSecure()
    {
        return $this->cookieSecure;
    }

    public function setCookieSecure(bool $cookieSecure)
    {
        $this->cookieSecure = $cookieSecure;
    }

    public function isCookieHttpOnly()
    {
        return $this->cookieHttpOnly;
    }

    public function setCookieHttpOnly(bool $cookieHttpOnly)
    {
        $this->cookieHttpOnly = $cookieHttpOnly;
    }

    public function isCookieRaw()
    {
        return $this->cookieRaw;
    }

    public function setCookieRaw(bool $cookieRaw = false)
    {
        $this->cookieRaw = $cookieRaw;
    }

    public function getCookieSameSite()
    {
        return $this->cookieSameSite;
    }

    public function setCookieSameSite(?string $cookieSameSite = Cookie::SAMESITE_LAX)
    {
        $this->cookieSameSite = $cookieSameSite;
    }

    /**
     * Modifies the Response for the specified device view.
     *
     * @param string $view the device view for which the response should be modified
     */
    public function modifyResponse(string $view, Response $response)
    {
        $response->headers->setCookie($this->createCookie($view));

        return $response;
    }

    /**
     * Gets the RedirectResponse for the specified device view.
     *
     * @param string $view       The device view for which we want the RedirectResponse
     * @param string $host       Uri host
     * @param int    $statusCode Status code
     */
    public function getRedirectResponse(string $view, string $host, int $statusCode)
    {
        return new RedirectResponseWithCookie($host, $statusCode, $this->createCookie($view));
    }

    public function getSwitchParam()
    {
        return $this->switchParam;
    }

    public function setSwitchParam(string $switchParam)
    {
        $this->switchParam = $switchParam;
    }

    protected function getStatusCode(string $view)
    {
        if (isset($this->getRedirectConfig()[$view]['status_code'])) {
            return $this->getRedirectConfig()[$view]['status_code'];
        }

        return Response::HTTP_FOUND;
    }

    /**
     * Create the Cookie object.
     */
    protected function createCookie(string $value)
    {
        try {
            $expire = new \DateTime($this->getCookieExpireDatetimeModifier());
        } catch (\Exception $e) {
            $expire = new \DateTime(self::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT);
        }

        return Cookie::create(
            $this->getCookieKey(),
            $value,
            $expire,
            $this->getCookiePath(),
            $this->getCookieDomain(),
            $this->isCookieSecure(),
            $this->isCookieHttpOnly(),
            $this->isCookieRaw(),
            $this->getCookieSameSite()
        );
    }
}
