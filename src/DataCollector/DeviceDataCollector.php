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

use MobileDetectBundle\EventListener\RequestResponseListener;
use MobileDetectBundle\Helper\DeviceView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * @author Jonas HAOUZI <haouzijonas@gmail.com>
 */
class DeviceDataCollector extends DataCollector
{
    /**
     * @param array<string, mixed> $redirectConfig
     */
    public function __construct(
        protected readonly DeviceView $deviceView,
        protected readonly array $redirectConfig = [],
    ) {
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
                'type' => DeviceView::VIEW_DESKTOP,
                'label' => 'Desktop',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceView::VIEW_DESKTOP,
                ),
                'isCurrent' => $this->deviceView->isDesktopView(),
                'enabled' => $this->canUseView(DeviceView::VIEW_DESKTOP, $request->getSchemeAndHttpHost()),
            ],
            [
                'type' => DeviceView::VIEW_TABLET,
                'label' => 'Tablet',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceView::VIEW_TABLET,
                ),
                'isCurrent' => $this->deviceView->isTabletView(),
                'enabled' => $this->canUseView(DeviceView::VIEW_TABLET, $request->getSchemeAndHttpHost()),
            ],
            [
                'type' => DeviceView::VIEW_MOBILE,
                'label' => 'Mobile',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceView::VIEW_MOBILE,
                ),
                'isCurrent' => $this->deviceView->isMobileView(),
                'enabled' => $this->canUseView(DeviceView::VIEW_MOBILE, $request->getSchemeAndHttpHost()),
            ],
        ];
    }

    public function getCurrentView(): string
    {
        return $this->data['currentView'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getViews(): array
    {
        return $this->data['views'];
    }

    public function getName(): string
    {
        return 'device.collector';
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array|Data
    {
        return $this->data;
    }

    public function reset(): void
    {
        $this->data = [];
    }

    protected function canUseView(string $view, ?string $host): bool
    {
        if (!isset($this->redirectConfig[$view])
            || !isset($this->redirectConfig[$view]['is_enabled'])
            || false === $this->redirectConfig[$view]['is_enabled']
        ) {
            return true;
        }

        if (true === $this->redirectConfig[$view]['is_enabled']
            && isset($this->redirectConfig[$view]['host'], $this->redirectConfig[$view]['action'])
            && !empty($this->redirectConfig[$view]['host'])
            && \in_array($this->redirectConfig[$view]['action'], [RequestResponseListener::REDIRECT, RequestResponseListener::REDIRECT_WITHOUT_PATH], true)
        ) {
            $parseHost = parse_url($this->redirectConfig[$view]['host']);
            $redirectHost = ($parseHost['scheme'] ?? '').'://'.($parseHost['host'] ?? '');
            if (!empty($parseHost['port'])) {
                $redirectHost .= ':'.$parseHost['port'];
            }

            if ($redirectHost !== $host) {
                return false;
            }
        }

        return true;
    }

    private function generateSwitchLink(
        Request $request,
        string $view,
    ): string {
        $requestSwitchView = $request->duplicate();
        $requestSwitchView->query->set($this->deviceView->getSwitchParam(), $view);
        $requestSwitchView->server->set(
            'QUERY_STRING',
            Request::normalizeQueryString(
                http_build_query($requestSwitchView->query->all()),
            ),
        );

        return $requestSwitchView->getUri();
    }
}
