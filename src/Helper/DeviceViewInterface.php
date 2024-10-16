<?php

declare(strict_types=1);

namespace MobileDetectBundle\Helper;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 */
interface DeviceViewInterface
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
     * Gets the view type for a device.
     */
    public function getViewType(): ?string;

    /**
     * Gets the view type that has explicitly been requested either by switch param, or by cookie.
     *
     * @return string the requested view type or null if no view type has been explicitly requested
     */
    public function getRequestedViewType(): ?string;

    /**
     * Is the device in full view.
     */
    public function isFullView(): bool;

    public function isTabletView(): bool;

    public function isMobileView(): bool;

    /**
     * Is not the device a mobile view type (PC, Mac, etc.).
     */
    public function isNotMobileView(): bool;

    /**
     * Has the Request the switch param in the query string (GET header).
     */
    public function hasSwitchParam(): bool;

    public function setView(string $view): void;

    /**
     * Sets the full (desktop) view type.
     */
    public function setFullView(): void;

    public function setTabletView(): void;

    public function setMobileView(): void;

    public function setNotMobileView(): void;

    public function getRedirectConfig(): array;

    public function setRedirectConfig(array $redirectConfig): void;

    /**
     * Retrieves a RedirectResponseWithCookie object based on the provided redirect URL.
     */
    public function getRedirectResponseBySwitchParam(string $redirectUrl): RedirectResponseWithCookie;

    /**
     * Gets the switch param value from the query string (GET header).
     */
    public function getSwitchParamValue(): ?string;

    public function getCookieExpireDatetimeModifier(): string;

    public function setCookieExpireDatetimeModifier(string $cookieExpireDatetimeModifier): void;

    public function getCookieKey(): string;

    public function setCookieKey(string $cookieKey): void;

    public function getCookiePath(): string;

    public function setCookiePath(string $cookiePath): void;

    public function getCookieDomain(): string;

    public function setCookieDomain(string $cookieDomain): void;

    public function isCookieSecure(): bool;

    public function setCookieSecure(bool $cookieSecure): void;

    public function isCookieHttpOnly(): bool;

    public function setCookieHttpOnly(bool $cookieHttpOnly): void;

    public function isCookieRaw(): bool;

    public function setCookieRaw(bool $cookieRaw = false): void;

    public function getCookieSameSite(): ?string;

    public function setCookieSameSite(?string $cookieSameSite = Cookie::SAMESITE_LAX): void;

    /**
     * Modifies the Response for the specified device view.
     *
     * @param string $view the device view for which the response should be modified
     */
    public function modifyResponse(string $view, Response $response): Response;

    /**
     * Gets the RedirectResponse for the specified device view.
     *
     * @param string $view       The device view for which we want the RedirectResponse
     * @param string $host       Uri host
     * @param int    $statusCode Status code
     */
    public function getRedirectResponse(string $view, string $host, int $statusCode): RedirectResponseWithCookie;

    public function getSwitchParam(): string;

    public function setSwitchParam(string $switchParam): void;
}
