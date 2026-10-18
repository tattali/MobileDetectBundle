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

use MobileDetectBundle\DependencyInjection\Configuration;
use MobileDetectBundle\EventListener\RequestResponseListener;
use MobileDetectBundle\Helper\DeviceView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * @author Jonas HAOUZI <haouzijonas@gmail.com>
 *
 * @phpstan-import-type RedirectConfig from Configuration
 */
class DeviceDataCollector extends DataCollector
{
    /**
     * @param RedirectConfig $redirectConfig
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
        $currentView = $this->data['currentView'] ?? null;

        return \is_string($currentView) ? $currentView : '';
    }

    /**
     * @return array<mixed>
     */
    public function getViews(): array
    {
        $views = $this->data['views'] ?? null;

        return \is_array($views) ? $views : [];
    }

    public function getName(): string
    {
        return 'device.collector';
    }

    /**
     * @return array<mixed>|Data
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
        $viewConfig = $this->redirectConfig[$view] ?? null;
        if (!\is_array($viewConfig) || true !== ($viewConfig['is_enabled'] ?? false)) {
            return true;
        }

        $configHost = $viewConfig['host'] ?? null;
        if (null !== $configHost && '' !== $configHost && '0' !== $configHost
            && \in_array($viewConfig['action'] ?? null, [RequestResponseListener::REDIRECT, RequestResponseListener::REDIRECT_WITHOUT_PATH], true)
        ) {
            $parseHost = parse_url($configHost);
            if (false === $parseHost) {
                $parseHost = [];
            }
            $redirectHost = ($parseHost['scheme'] ?? '').'://'.($parseHost['host'] ?? '');
            if (isset($parseHost['port']) && 0 !== $parseHost['port']) {
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
