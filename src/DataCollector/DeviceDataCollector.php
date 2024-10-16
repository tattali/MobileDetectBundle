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

namespace MobileDetectBundle\DataCollector;

use MobileDetectBundle\EventListener\RequestResponseListenerInterface;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Helper\DeviceViewInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * @author Jonas HAOUZI <haouzijonas@gmail.com>
 */
class DeviceDataCollector extends DataCollector implements DeviceDataCollectorInterface
{
    /**
     * @var DeviceView
     */
    protected $deviceView;

    /**
     * @var array
     */
    protected $redirectConfig;

    public function __construct(DeviceView $deviceView)
    {
        $this->deviceView = $deviceView;
    }

    /**
     * Collects data for the given Request and Response.
     */
    public function collect(
        Request $request,
        Response $response,
        ?\Throwable $exception = null,
    ): void {
        $this->data['currentView'] = $this->deviceView->getViewType();
        $this->data['views'] = [
            [
                'type' => DeviceViewInterface::VIEW_FULL,
                'label' => 'Full',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceViewInterface::VIEW_FULL
                ),
                'isCurrent' => $this->deviceView->isFullView(),
                'enabled' => $this->canUseView(DeviceViewInterface::VIEW_FULL, $request->getSchemeAndHttpHost()),
            ],
            [
                'type' => DeviceViewInterface::VIEW_TABLET,
                'label' => 'Tablet',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceViewInterface::VIEW_TABLET
                ),
                'isCurrent' => $this->deviceView->isTabletView(),
                'enabled' => $this->canUseView(DeviceViewInterface::VIEW_TABLET, $request->getSchemeAndHttpHost()),
            ],
            [
                'type' => DeviceViewInterface::VIEW_MOBILE,
                'label' => 'Mobile',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceViewInterface::VIEW_MOBILE
                ),
                'isCurrent' => $this->deviceView->isMobileView(),
                'enabled' => $this->canUseView(DeviceViewInterface::VIEW_MOBILE, $request->getSchemeAndHttpHost()),
            ],
        ];
    }

    /**
     * Get the current view being displayed.
     */
    public function getCurrentView(): string
    {
        return $this->data['currentView'];
    }

    /**
     * Gets the views from the data array.
     */
    public function getViews(): array
    {
        return $this->data['views'];
    }

    /**
     * Sets the redirect configuration.
     */
    public function setRedirectConfig(array $redirectConfig): void
    {
        $this->redirectConfig = $redirectConfig;
    }

    /**
     * Get the name of the collector device.
     */
    public function getName(): string
    {
        return 'device.collector';
    }

    /**
     * Gets the data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Resets the data to an empty array.
     */
    public function reset(): void
    {
        $this->data = [];
    }

    /**
     * Check if the view can be used based on configuration settings.
     */
    protected function canUseView(string $view, ?string $host): bool
    {
        if (!\is_array($this->redirectConfig)
            || !isset($this->redirectConfig[$view])
            || !isset($this->redirectConfig[$view]['is_enabled'])
            || false === $this->redirectConfig[$view]['is_enabled']
        ) {
            return true;
        }

        if (true === $this->redirectConfig[$view]['is_enabled']
            && isset($this->redirectConfig[$view]['host'], $this->redirectConfig[$view]['action'])
            && !empty($this->redirectConfig[$view]['host'])
            && \in_array($this->redirectConfig[$view]['action'], [RequestResponseListenerInterface::REDIRECT, RequestResponseListenerInterface::REDIRECT_WITHOUT_PATH], true)
        ) {
            $parseHost = parse_url((string) $this->redirectConfig[$view]['host']);
            $redirectHost = $parseHost['scheme'].'://'.$parseHost['host'];
            if (!empty($parseHost['port'])) {
                $redirectHost .= ':'.$parseHost['port'];
            }

            if ($redirectHost !== $host) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generates a switch link for switching the view based on the provided view parameter.
     */
    private function generateSwitchLink(
        Request $request,
        string $view,
    ): ?string {
        $requestSwitchView = $request->duplicate();
        $requestSwitchView->query->set($this->deviceView->getSwitchParam(), $view);
        $requestSwitchView->server->set(
            'QUERY_STRING',
            Request::normalizeQueryString(
                http_build_query($requestSwitchView->query->all())
            )
        );

        return $requestSwitchView->getUri();
    }
}
