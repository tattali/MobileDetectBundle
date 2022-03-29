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
 */
class DeviceDataCollector extends DataCollector
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
     *
     * @param Request                    $request   A Request instance
     * @param Response                   $response  A Response instance
     * @param \Throwable|\Exception|null $exception An Exception instance
     */
    public function collect(
        Request $request,
        Response $response,
        $exception = null
    ) {
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
        return $this->data['currentView'];
    }

    public function getViews(): array
    {
        return $this->data['views'];
    }

    public function setRedirectConfig(array $redirectConfig): void
    {
        $this->redirectConfig = $redirectConfig;
    }

    public function getName(): string
    {
        return 'device.collector';
    }

    public function reset()
    {
        $this->data = [];
    }

    /**
     * @param $view
     * @param $host
     *
     * @return bool
     */
    protected function canUseView($view, $host)
    {
        if (!\is_array($this->redirectConfig)) {
            return true;
        }

        if (!isset($this->redirectConfig[$view])) {
            return true;
        }

        if (!isset($this->redirectConfig[$view]['is_enabled'])
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
     * @param $view
     *
     * @return string
     */
    private function generateSwitchLink(
        Request $request,
        $view
    ) {
        $requestSwitchView = $request->duplicate();
        $requestSwitchView->query->set($this->deviceView->getSwitchParam(), $view);
        $requestSwitchView->server->set(
            'QUERY_STRING',
            Request::normalizeQueryString(
                http_build_query($requestSwitchView->query->all(), '', '&')
            )
        );

        return $requestSwitchView->getUri();
    }
}
