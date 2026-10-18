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
 */
final class DeviceDataCollectorTest extends TestCase
{
    /**
     * @var MockObject&RequestStack
     */
    private $requestStack;

    /**
     * @var MockObject&Request
     */
    private $request;

    /**
     * @var MockObject&Response
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
        $redirectConfig = [
            'tablet' => [
                'is_enabled' => true,
                'host' => 'http://t.testsite.com',
                'status_code' => Response::HTTP_FOUND,
                'action' => RequestResponseListener::REDIRECT,
            ],
        ];
        $this->request->cookies = new InputBag([DeviceView::COOKIE_KEY_DEFAULT => DeviceView::VIEW_MOBILE]);
        $this->request->expects(self::any())->method('getUri')->willReturn('http://testsite.com/');
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

    public function testCollectWithoutViewReportsAnEmptyCurrentView(): void
    {
        // The request listener did not run (e.g. the router answered 404 first) and
        // neither the switch param nor the cookie are set: the view is unknown.
        $request = Request::create('http://testsite.com/page');
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $deviceView = new DeviceView($requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $deviceDataCollector->collect($request, new Response());

        self::assertNull($deviceView->getViewType());
        self::assertSame('', $deviceDataCollector->getCurrentView());
    }

    public function testCollectWithRedirectHostWithoutSchemeMarksTheViewExternal(): void
    {
        $request = Request::create('http://testsite.com/page');
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $deviceDataCollector = new DeviceDataCollector(new DeviceView($requestStack));
        $deviceDataCollector->setRedirectConfig([
            DeviceView::VIEW_MOBILE => [
                'is_enabled' => true,
                'host' => 'm.testsite.com',
                'status_code' => Response::HTTP_FOUND,
                'action' => RequestResponseListener::REDIRECT,
            ],
        ]);
        $deviceDataCollector->collect($request, new Response());

        $mobileView = $deviceDataCollector->getViews()[2];
        self::assertIsArray($mobileView);
        self::assertSame(DeviceView::VIEW_MOBILE, $mobileView['type']);
        self::assertFalse($mobileView['enabled']);
    }

    public function testCollectCurrentViewMobileCanUseTablet(): void
    {
        $redirectConfig = [
            'tablet' => [
                'is_enabled' => true,
                'host' => 'http://t.testsite.com',
                'status_code' => Response::HTTP_FOUND,
                'action' => RequestResponseListener::REDIRECT,
            ],
        ];
        $this->request->query = new InputBag(['param1' => 'value1']);
        $this->request->expects(self::any())->method('getSchemeAndHttpHost')->willReturn('http://t.testsite.com');
        $this->request->expects(self::any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/path-info');
                $this->request->expects(self::any())->method('getQueryString')->willReturnCallback(function () {
            $qs = Request::normalizeQueryString($this->queryString());

            return '' === $qs ? null : $qs;
        });
        $this->request->expects(self::any())->method('getUri')->willReturnCallback(function () {
            if (null !== $qs = $this->request->getQueryString()) {
                $qs = '?'.$qs;
            }

            return $this->request->getSchemeAndHttpHost().$this->request->getBaseUrl().$this->request->getPathInfo().$qs;
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
        $redirectConfig = [
            'tablet' => [
                'is_enabled' => true,
                'host' => 'http://t.testsite.com',
                'status_code' => Response::HTTP_FOUND,
                'action' => RequestResponseListener::REDIRECT,
            ],
        ];
        $this->request->query = new InputBag(['param1' => 'value1']);
        $this->request->expects(self::any())->method('getSchemeAndHttpHost')->willReturn('http://t.testsite.com');
        $this->request->expects(self::any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/path-info');
                $this->request->expects(self::any())->method('getQueryString')->willReturnCallback(function () {
            $qs = Request::normalizeQueryString($this->queryString());

            return '' === $qs ? null : $qs;
        });
        $this->request->expects(self::any())->method('getUri')->willReturnCallback(function () {
            if (null !== $qs = $this->request->getQueryString()) {
                $qs = '?'.$qs;
            }

            return $this->request->getSchemeAndHttpHost().$this->request->getBaseUrl().$this->request->getPathInfo().$qs;
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
        $redirectConfig = [
            'mobile' => [
                'is_enabled' => true,
                'host' => 'http://m.testsite.com',
                'status_code' => Response::HTTP_FOUND,
                'action' => RequestResponseListener::REDIRECT,
            ],
        ];
        $this->request->query = new InputBag(['param1' => 'value1']);
        $this->request->expects(self::any())->method('getSchemeAndHttpHost')->willReturn('http://testsite.com');
        $this->request->expects(self::any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/path-info');
                $this->request->expects(self::any())->method('getQueryString')->willReturnCallback(function () {
            $qs = Request::normalizeQueryString($this->queryString());

            return '' === $qs ? null : $qs;
        });
        $this->request->expects(self::any())->method('getUri')->willReturnCallback(function () {
            if (null !== $qs = $this->request->getQueryString()) {
                $qs = '?'.$qs;
            }

            return $this->request->getSchemeAndHttpHost().$this->request->getBaseUrl().$this->request->getPathInfo().$qs;
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

    private function queryString(): ?string
    {
        $queryString = $this->request->server->get('QUERY_STRING');
        self::assertTrue(null === $queryString || \is_string($queryString));

        return $queryString;
    }
}
