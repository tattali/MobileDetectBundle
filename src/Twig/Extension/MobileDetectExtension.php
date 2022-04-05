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

use MobileDetectBundle\DeviceDetector\MobileDetector;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
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
    /**
     * @var MobileDetectorInterface
     */
    private $mobileDetector;

    /**
     * @var DeviceView
     */
    private $deviceView;

    /**
     * @var array
     */
    private $redirectConf;

    /**
     * @var Request
     */
    private $request;

    public function __construct(RequestStack $requestStack, MobileDetectorInterface $mobileDetector, DeviceView $deviceView, array $redirectConf)
    {
        $this->request = method_exists(RequestStack::class, 'getMainRequest') ? $requestStack->getMainRequest() : $requestStack->getMasterRequest();
        $this->mobileDetector = $mobileDetector;
        $this->deviceView = $deviceView;
        $this->redirectConf = $redirectConf;
    }

    /**
     * Get extension twig function.
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('is_mobile', [$this, 'isMobile']),
            new TwigFunction('is_tablet', [$this, 'isTablet']),
            new TwigFunction('is_device', [$this, 'isDevice']),
            new TwigFunction('is_full_view', [$this, 'isFullView']),
            new TwigFunction('is_mobile_view', [$this, 'isMobileView']),
            new TwigFunction('is_tablet_view', [$this, 'isTabletView']),
            new TwigFunction('is_not_mobile_view', [$this, 'isNotMobileView']),
            new TwigFunction('is_ios', [$this, 'isIOS']),
            new TwigFunction('is_android_os', [$this, 'isAndroidOS']),
            new TwigFunction('is_windows_os', [$this, 'isWindowsOS']),
            new TwigFunction('full_view_url', [$this, 'fullViewUrl'], ['is_safe' => ['html']]),
            new TwigFunction('device_version', [$this, 'deviceVersion']),
            new TwigFunction('rules_list', [$this, 'getRules']),
        ];
    }

    public function getRules()
    {
        return array_merge(
            $this->mobileDetector->getPhoneDevices(),
            $this->mobileDetector->getTabletDevices(),
            $this->mobileDetector->getOperatingSystems(),
            $this->mobileDetector->getBrowsers(),
            $this->mobileDetector->getUtilities()
        );
    }

    /**
     * Check the version of the given property in the User-Agent.
     * Will return a float number. (eg. 2_0 will return 2.0, 4.3.1 will return 4.31).
     *
     * @param string $propertyName The name of the property. See self::getProperties() array
     *                             keys for all possible properties.
     * @param string $type         Either self::VERSION_TYPE_STRING to get a string value or
     *                             self::VERSION_TYPE_FLOAT indicating a float value. This parameter
     *                             is optional and defaults to self::VERSION_TYPE_STRING. Passing an
     *                             invalid parameter will default to the this type as well.
     *
     * @return string|float the version of the property we are trying to extract
     */
    public function deviceVersion(string $propertyName, string $type = MobileDetector::VERSION_TYPE_STRING)
    {
        return $this->mobileDetector->version($propertyName, $type) ?: null;
    }

    /**
     * Regardless of the current view, returns the URL that leads to the equivalent page
     * in the full/desktop view. This is useful for generating <link rel="canonical"> tags
     * on mobile pages for Search Engine Optimization.
     * See: http://searchengineland.com/the-definitive-guide-to-mobile-technical-seo-166066.
     */
    public function fullViewUrl(bool $addCurrentPathAndQuery = true)
    {
        if (!isset($this->redirectConf[DeviceView::VIEW_FULL]['host'])) {
            // The host property has not been configured for the full view
            return null;
        }

        $fullHost = $this->redirectConf[DeviceView::VIEW_FULL]['host'];

        if (empty($fullHost)) {
            return null;
        }

        // If not in request scope, we can only return the base URL to the full view
        if (!$this->request) {
            return $fullHost;
        }

        if (false === $addCurrentPathAndQuery) {
            return $fullHost;
        }

        // if fullHost ends with /, skip it since getPathInfo() also starts with /
        $result = rtrim($fullHost, '/').$this->request->getPathInfo();

        $query = Request::normalizeQueryString(http_build_query($this->request->query->all(), '', '&'));
        if ($query) {
            $result .= '?'.$query;
        }

        return $result;
    }

    public function isMobile()
    {
        return $this->mobileDetector->isMobile();
    }

    public function isTablet()
    {
        return $this->mobileDetector->isTablet();
    }

    /**
     * @param string $deviceName is[iPhone|BlackBerry|HTC|Nexus|Dell|Motorola|Samsung|Sony|Asus|Palm|Vertu|...]
     */
    public function isDevice(string $deviceName)
    {
        $magicMethodName = 'is'.strtolower((string) $deviceName);

        return $this->mobileDetector->{$magicMethodName}();
    }

    public function isFullView()
    {
        return $this->deviceView->isFullView();
    }

    public function isMobileView()
    {
        return $this->deviceView->isMobileView();
    }

    public function isTabletView()
    {
        return $this->deviceView->isTabletView();
    }

    public function isNotMobileView()
    {
        return $this->deviceView->isNotMobileView();
    }

    public function isIOS()
    {
        return $this->mobileDetector->isIOS();
    }

    public function isAndroidOS()
    {
        return $this->mobileDetector->isAndroidOS();
    }

    public function isWindowsOS()
    {
        return $this->mobileDetector->isWindowsMobileOS() || $this->mobileDetector->isWindowsPhoneOS();
    }
}
