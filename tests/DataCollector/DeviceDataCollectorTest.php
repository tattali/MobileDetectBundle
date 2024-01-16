<?php

declare(strict_types=1);

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MobileDetectBundle\Tests\DataCollector;

use MobileDetectBundle\DataCollector\DeviceDataCollector;
use MobileDetectBundle\EventListener\RequestResponseListener;
use MobileDetectBundle\Helper\DeviceView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ServerBag;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 *
 * @internal
 *
 * @coversDefaultClass
 */
final class DeviceDataCollectorTest extends TestCase
{
    /**
     * @var MockObject|RequestStack
     */
    private $requestStack;

    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var MockObject|Response
     */
    private $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->getMockBuilder(Request::class)->getMock();
        $this->request->query = new InputBag();
        $this->request->cookies = new InputBag();
        $this->request->server = new ServerBag();
        $this->request->expects(self::any())->method('duplicate')->willReturn($this->request);

        $this->requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request)
        ;

        $this->response = $this->getMockBuilder(Response::class)->getMock();
    }

    public function testCollectCurrentViewMobileIsCurrent(): void
    {
        $redirectConfig['tablet'] = [
            'is_enabled' => true,
            'host' => 'http://t.testsite.com',
            'status_code' => Response::HTTP_FOUND,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->cookies = new InputBag([DeviceView::COOKIE_KEY_DEFAULT => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $deviceDataCollector->setRedirectConfig($redirectConfig);
        $deviceDataCollector->collect($this->request, $this->response);

        $currentView = $deviceDataCollector->getCurrentView();
        $views = $deviceDataCollector->getViews();

        self::assertSame($deviceView->getViewType(), $currentView);
        self::assertSame(DeviceView::VIEW_MOBILE, $currentView);
        self::assertCount(3, $views);

        foreach ($views as $view) {
            self::assertIsArray($view);
            self::assertArrayHasKey('type', $view);
            self::assertArrayHasKey('label', $view);
            self::assertArrayHasKey('link', $view);
            self::assertArrayHasKey('isCurrent', $view);
            self::assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                self::assertTrue($view['isCurrent']);
            }
        }
    }

    public function testCollectCurrentViewMobileCanUseTablet(): void
    {
        $redirectConfig['tablet'] = [
            'is_enabled' => true,
            'host' => 'http://t.testsite.com',
            'status_code' => Response::HTTP_FOUND,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->query = new InputBag(['param1' => 'value1']);
        $this->request->expects(self::any())->method('getSchemeAndHttpHost')->willReturn('http://t.testsite.com');
        $this->request->expects(self::any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/path-info');
        $test = $this;
        $this->request->expects(self::any())->method('getQueryString')->willReturnCallback(static function () use ($test) {
            $qs = Request::normalizeQueryString($test->request->server->get('QUERY_STRING'));

            return '' === $qs ? null : $qs;
        });
        $this->request->expects(self::any())->method('getUri')->willReturnCallback(static function () use ($test) {
            if (null !== $qs = $test->request->getQueryString()) {
                $qs = '?'.$qs;
            }

            return $test->request->getSchemeAndHttpHost().$test->request->getBaseUrl().$test->request->getPathInfo().$qs;
        });
        $this->request->cookies = new InputBag([DeviceView::COOKIE_KEY_DEFAULT => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $deviceDataCollector->setRedirectConfig($redirectConfig);
        $deviceDataCollector->collect($this->request, $this->response);

        $currentView = $deviceDataCollector->getCurrentView();
        $views = $deviceDataCollector->getViews();

        self::assertSame($deviceView->getViewType(), $currentView);
        self::assertSame(DeviceView::VIEW_MOBILE, $currentView);
        self::assertCount(3, $views);

        foreach ($views as $view) {
            self::assertIsArray($view);
            self::assertArrayHasKey('type', $view);
            self::assertArrayHasKey('label', $view);
            self::assertArrayHasKey('link', $view);
            self::assertArrayHasKey('isCurrent', $view);
            self::assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                self::assertTrue($view['isCurrent']);
            }
            if (DeviceView::VIEW_TABLET === $view['type']) {
                self::assertFalse($view['isCurrent']);
                self::assertTrue($view['enabled']);
                self::assertSame(
                    sprintf(
                        'http://t.testsite.com/base-url/path-info?%s=%s&param1=value1',
                        $deviceView->getSwitchParam(),
                        DeviceView::VIEW_TABLET
                    ),
                    $view['link']
                );
            }
        }
    }

    public function testCollectCurrentViewFullCanUseMobile(): void
    {
        $redirectConfig['tablet'] = [
            'is_enabled' => true,
            'host' => 'http://t.testsite.com',
            'status_code' => Response::HTTP_FOUND,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->query = new InputBag(['param1' => 'value1']);
        $this->request->expects(self::any())->method('getSchemeAndHttpHost')->willReturn('http://t.testsite.com');
        $this->request->expects(self::any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/path-info');
        $test = $this;
        $this->request->expects(self::any())->method('getQueryString')->willReturnCallback(static function () use ($test) {
            $qs = Request::normalizeQueryString($test->request->server->get('QUERY_STRING'));

            return '' === $qs ? null : $qs;
        });
        $this->request->expects(self::any())->method('getUri')->willReturnCallback(static function () use ($test) {
            if (null !== $qs = $test->request->getQueryString()) {
                $qs = '?'.$qs;
            }

            return $test->request->getSchemeAndHttpHost().$test->request->getBaseUrl().$test->request->getPathInfo().$qs;
        });
        $this->request->cookies = new InputBag([DeviceView::COOKIE_KEY_DEFAULT => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $deviceDataCollector->setRedirectConfig($redirectConfig);
        $deviceDataCollector->collect($this->request, $this->response);

        $currentView = $deviceDataCollector->getCurrentView();
        $views = $deviceDataCollector->getViews();

        self::assertSame($deviceView->getViewType(), $currentView);
        self::assertSame(DeviceView::VIEW_FULL, $currentView);
        self::assertCount(3, $views);

        foreach ($views as $view) {
            self::assertIsArray($view);
            self::assertArrayHasKey('type', $view);
            self::assertArrayHasKey('label', $view);
            self::assertArrayHasKey('link', $view);
            self::assertArrayHasKey('isCurrent', $view);
            self::assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_FULL === $view['type']) {
                self::assertTrue($view['isCurrent']);
            }
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                self::assertFalse($view['isCurrent']);
                self::assertTrue($view['enabled']);
                self::assertSame(
                    sprintf(
                        'http://t.testsite.com/base-url/path-info?%s=%s&param1=value1',
                        $deviceView->getSwitchParam(),
                        DeviceView::VIEW_MOBILE
                    ),
                    $view['link']
                );
            }
        }
    }

    public function testCollectCurrentViewFullCantUseMobile(): void
    {
        $redirectConfig['mobile'] = [
            'is_enabled' => true,
            'host' => 'http://m.testsite.com',
            'status_code' => Response::HTTP_FOUND,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->query = new InputBag(['param1' => 'value1']);
        $this->request->expects(self::any())->method('getSchemeAndHttpHost')->willReturn('http://testsite.com');
        $this->request->expects(self::any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/path-info');
        $test = $this;
        $this->request->expects(self::any())->method('getQueryString')->willReturnCallback(static function () use ($test) {
            $qs = Request::normalizeQueryString($test->request->server->get('QUERY_STRING'));

            return '' === $qs ? null : $qs;
        });
        $this->request->expects(self::any())->method('getUri')->willReturnCallback(static function () use ($test) {
            if (null !== $qs = $test->request->getQueryString()) {
                $qs = '?'.$qs;
            }

            return $test->request->getSchemeAndHttpHost().$test->request->getBaseUrl().$test->request->getPathInfo().$qs;
        });
        $this->request->cookies = new InputBag([DeviceView::COOKIE_KEY_DEFAULT => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $deviceDataCollector->setRedirectConfig($redirectConfig);
        $deviceDataCollector->collect($this->request, $this->response);

        $currentView = $deviceDataCollector->getCurrentView();
        $views = $deviceDataCollector->getViews();

        self::assertSame($deviceView->getViewType(), $currentView);
        self::assertSame(DeviceView::VIEW_FULL, $currentView);
        self::assertCount(3, $views);

        foreach ($views as $view) {
            self::assertIsArray($view);
            self::assertArrayHasKey('type', $view);
            self::assertArrayHasKey('label', $view);
            self::assertArrayHasKey('link', $view);
            self::assertArrayHasKey('isCurrent', $view);
            self::assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_FULL === $view['type']) {
                self::assertTrue($view['isCurrent']);
            }
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                self::assertFalse($view['isCurrent']);
                self::assertFalse($view['enabled']);
                self::assertSame(
                    sprintf(
                        'http://testsite.com/base-url/path-info?%s=%s&param1=value1',
                        $deviceView->getSwitchParam(),
                        DeviceView::VIEW_MOBILE
                    ),
                    $view['link']
                );
            }
        }
    }

    public function testReset(): void
    {
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $deviceDataCollector->reset();
        self::assertSame([], $deviceDataCollector->getData());
    }

    public function testGetNameValue(): void
    {
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        self::assertSame('device.collector', $deviceDataCollector->getName());
    }
}
