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

namespace MobileDetectBundle\Twig\Extension;

use Detection\MobileDetect;
use MobileDetectBundle\Helper\DeviceView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 */
class MobileDetectExtension extends AbstractExtension
{
    private ?Request $request;

    /**
     * @param array<string, mixed> $redirectConf
     */
    public function __construct(
        RequestStack $requestStack,
        private readonly MobileDetect $mobileDetect,
        private readonly DeviceView $deviceView,
        private readonly array $redirectConf,
    ) {
        $this->request = $requestStack->getMainRequest();
    }

    /**
     * Get extension twig function.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_mobile', [$this, 'isMobile']),
            new TwigFunction('is_tablet', [$this, 'isTablet']),
            new TwigFunction('is_device', [$this, 'isDevice']),
            new TwigFunction('is_desktop_view', [$this, 'isDesktopView']),
            new TwigFunction('is_mobile_view', [$this, 'isMobileView']),
            new TwigFunction('is_tablet_view', [$this, 'isTabletView']),
            new TwigFunction('is_not_mobile_view', [$this, 'isNotMobileView']),
            new TwigFunction('is_ios', [$this, 'isiOS']),
            new TwigFunction('is_android_os', [$this, 'isAndroidOS']),
            new TwigFunction('is_windows_os', [$this, 'isWindowsOS']),
            new TwigFunction('desktop_view_url', [$this, 'desktopViewUrl'], ['is_safe' => ['html']]),
            new TwigFunction('device_version', [$this, 'deviceVersion']),
            new TwigFunction('rules_list', [$this, 'getRules']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        return $this->mobileDetect->getRules();
    }

    public function deviceVersion(string $propertyName, string $type = ''): float|bool|string|null
    {
        return $this->mobileDetect->version($propertyName, $type) ?: null;
    }

    /**
     * Regardless of the current view, returns the URL that leads to the equivalent page
     * in the desktop/desktop view. This is useful for generating <link rel="canonical"> tags
     * on mobile pages for Search Engine Optimization.
     * See: http://searchengineland.com/the-definitive-guide-to-mobile-technical-seo-166066.
     */
    public function desktopViewUrl(bool $addCurrentPathAndQuery = true): ?string
    {
        if (!isset($this->redirectConf[DeviceView::VIEW_DESKTOP]['host'])) {
            // The host property has not been configured for the desktop view
            return null;
        }

        $desktopHost = $this->redirectConf[DeviceView::VIEW_DESKTOP]['host'];

        if (empty($desktopHost)) {
            return null;
        }

        // If not in request scope, we can only return the base URL to the desktop view
        if (!$this->request) {
            return $desktopHost;
        }

        if (false === $addCurrentPathAndQuery) {
            return $desktopHost;
        }

        // if desktopHost ends with /, skip it since getPathInfo() also starts with /
        $result = rtrim($desktopHost, '/').$this->request->getPathInfo();

        $query = Request::normalizeQueryString(http_build_query($this->request->query->all(), '', '&'));
        if ($query) {
            $result .= '?'.$query;
        }

        return $result;
    }

    public function isMobile(): bool
    {
        return $this->mobileDetect->isMobile();
    }

    public function isTablet(): bool
    {
        return $this->mobileDetect->isTablet();
    }

    /**
     * @param string $deviceName is[iPhone|BlackBerry|HTC|Nexus|Dell|Motorola|Samsung|Sony|Asus|Palm|Vertu|...]
     */
    public function isDevice(string $deviceName): bool
    {
        $magicMethodName = 'is'.strtolower((string) $deviceName);

        return $this->mobileDetect->{$magicMethodName}();
    }

    public function isDesktopView(): bool
    {
        return $this->deviceView->isDesktopView();
    }

    public function isMobileView(): bool
    {
        return $this->deviceView->isMobileView();
    }

    public function isTabletView(): bool
    {
        return $this->deviceView->isTabletView();
    }

    public function isNotMobileView(): bool
    {
        return $this->deviceView->isNotMobileView();
    }

    public function isiOS(): bool
    {
        return $this->mobileDetect->isiOS();
    }

    public function isAndroidOS(): bool
    {
        return $this->mobileDetect->isAndroidOS();
    }

    public function isWindowsOS(): bool
    {
        return $this->mobileDetect->isWindowsMobileOS() || $this->mobileDetect->isWindowsPhoneOS();
    }
}
