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

/**
 * @author Jonas HAOUZI <haouzijonas@gmail.com>
 *
 * @phpstan-import-type RedirectConfig from DeviceView
 */
class DeviceDataCollector extends DataCollector
{
    /**
     * @var DeviceView
     */
    protected $deviceView;

    /**
     * @var RedirectConfig|null
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
        \Throwable $exception = null
    ): void {
        $this->data['currentView'] = $this->deviceView->getViewType();
        $this->data['views'] = [
            [
                'type' => DeviceView::VIEW_FULL,
                'label' => 'Full',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceView::VIEW_FULL
                ),
                'isCurrent' => $this->deviceView->isFullView(),
                'enabled' => $this->canUseView(DeviceView::VIEW_FULL, $request->getSchemeAndHttpHost()),
            ],
            [
                'type' => DeviceView::VIEW_TABLET,
                'label' => 'Tablet',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceView::VIEW_TABLET
                ),
                'isCurrent' => $this->deviceView->isTabletView(),
                'enabled' => $this->canUseView(DeviceView::VIEW_TABLET, $request->getSchemeAndHttpHost()),
            ],
            [
                'type' => DeviceView::VIEW_MOBILE,
                'label' => 'Mobile',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceView::VIEW_MOBILE
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

    /**
     * @param RedirectConfig $redirectConfig
     */
    public function setRedirectConfig(array $redirectConfig): void
    {
        $this->redirectConfig = $redirectConfig;
    }

    public function getName(): string
    {
        return 'device.collector';
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return \is_array($this->data) ? $this->data : [];
    }

    public function reset(): void
    {
        $this->data = [];
    }

    protected function canUseView(string $view, ?string $host): bool
    {
        $viewConfig = $this->redirectConfig[$view] ?? null;
        if (!\is_array($viewConfig)
            || !isset($viewConfig['is_enabled'])
            || true !== $viewConfig['is_enabled']
        ) {
            return true;
        }

        if (isset($viewConfig['host'], $viewConfig['action'])
            && (bool) $viewConfig['host']
            && \in_array($viewConfig['action'], [RequestResponseListener::REDIRECT, RequestResponseListener::REDIRECT_WITHOUT_PATH], true)
        ) {
            $parseHost = parse_url($viewConfig['host']);
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
        string $view
    ): string {
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
