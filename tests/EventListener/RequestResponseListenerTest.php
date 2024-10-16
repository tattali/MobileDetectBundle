<?php

declare(strict_types=1);

namespace MobileDetectBundle\Tests\EventListener;

use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
use MobileDetectBundle\EventListener\RequestResponseListener;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Helper\RedirectResponseWithCookie;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 *
 * @coversDefaultClass
 */
final class RequestResponseListenerTest extends TestCase
{
    /**
     * @var MockObject|MobileDetectorInterface
     */
    private MockObject $mobileDetector;

    /**
     * @var MockObject|RequestStack
     */
    private MockObject $requestStack;

    /**
     * @var MockObject|Request
     */
    private MockObject $request;

    /**
     * @var MockObject|RouterInterface
     */
    private MockObject $router;

    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $switchParam = DeviceView::SWITCH_PARAM_DEFAULT;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mobileDetector = $this->createMock(MobileDetectorInterface::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->request = $this->getMockBuilder(Request::class)->getMock();
        $this->request->expects(self::any())->method('getSchemeAndHttpHost')->willReturn('http://testhost.com');
        $this->request->expects(self::any())->method('get')->willReturn('value');
        $this->request->expects(self::any())->method('getUriForPath')->willReturn('/');
        $this->request->query = new InputBag();
        $this->request->cookies = new InputBag();

        $this->requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request)
        ;

        $this->config = [
            'mobile' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ];
    }

    public function testHandleRequestHasSwitchParam(): void
    {
        $this->request->query = new InputBag(['myparam' => 'myvalue', $this->switchParam => DeviceView::VIEW_MOBILE]);
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/');
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_FOUND]]);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, []);
        $event = $this->createGetResponseEvent('some content');
        $event->getRequest()->headers->set('user-agent', 'agent');

        $listener->handleRequest($event);
        self::assertFalse($listener->needsResponseModification());

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestBis(): void
    {
        $this->request->query = new InputBag(['myparam' => 'myvalue', $this->switchParam => DeviceView::VIEW_MOBILE]);
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/');
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_FOUND]]);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, [], false);
        $event = $this->createGetResponseEvent('some content');
        $event->getRequest()->headers->set('user-agent', 'agent');

        $listener->handleRequest($event);
        self::assertFalse($listener->needsResponseModification());

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasSwitchParamAndQuery(): void
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->request->query = new InputBag(['myparam' => 'myvalue', $this->switchParam => DeviceView::VIEW_MOBILE]);
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/');
        $this->request->expects(self::any())->method('get')->willReturn('value');
        $this->router->expects(self::exactly(2))->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );

        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_FOUND]]);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);
        $event = $this->createGetResponseEvent('some content');
        $event->getRequest()->headers->set('user-agent', 'agent');

        $listener->handleRequest($event);
        self::assertFalse($listener->needsResponseModification());

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://mobilehost.com/?%s=%s&myparam=myvalue',
            $this->switchParam,
            DeviceView::VIEW_MOBILE
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestIsFullView(): void
    {
        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);
        self::assertFalse($deviceView->hasSwitchParam());
        self::assertNull($deviceView->getRequestedViewType());
        $getResponseEvent = $this->createGetResponseEvent('some content');
        $getResponseEvent->getRequest()->headers->set('user-agent', 'agent');

        $listener->handleRequest($getResponseEvent);
        self::assertTrue($listener->needsResponseModification());
        self::assertSame(DeviceView::VIEW_FULL, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        self::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Full view', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $responseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        self::assertInstanceOf(Response::class, $modifiedResponse);
        self::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        self::assertSame('Full view', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_FULL, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestIsNotMobileView(): void
    {
        $deviceView = new DeviceView();
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);
        self::assertFalse($deviceView->hasSwitchParam());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_NOT_MOBILE, $deviceView->getViewType());
        $getResponseEvent = $this->createGetResponseEvent('some content');
        $getResponseEvent->getRequest()->headers->set('user-agent', 'agent');

        $listener->handleRequest($getResponseEvent);
        self::assertFalse($listener->needsResponseModification());

        $requestEventResponse = $getResponseEvent->getResponse();
        self::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Not mobile view', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $responseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        self::assertInstanceOf(Response::class, $modifiedResponse);
        self::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        self::assertSame('Not mobile view', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        self::assertCount(0, $cookies);
    }

    public function testHandleRequestHasTabletRedirect(): void
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://t.testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->query = new InputBag(['some' => 'param']);
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->request->expects(self::any())->method('get')->willReturn('value');
        $this->router->expects(self::exactly(2))->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects(self::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $getResponseEvent->getRequest()->headers->set('user-agent', 'agent');

        $listener->handleRequest($getResponseEvent);
        self::assertFalse($deviceView->hasSwitchParam());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://t.testsite.com/some/parameters?%s=%s&some=param',
            $this->switchParam,
            DeviceView::VIEW_TABLET
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestWithDifferentSwitchParamRedirect(): void
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $switchParam = 'custom_param';

        $this->request->query = new InputBag(['some' => 'param']);
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(self::exactly(2))->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects(self::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setSwitchParam($switchParam);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $getResponseEvent->getRequest()->headers->set('user-agent', 'agent');

        $listener->handleRequest($getResponseEvent);
        self::assertFalse($deviceView->hasSwitchParam());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://testsite.com/some/parameters?%s=%s&some=param',
            $switchParam,
            DeviceView::VIEW_TABLET
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleDeviceIsTabletAndTabletRedirectIsDisabledAndDetectTabletAsMobileIsFalse(): void
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->mobileDetector->expects(self::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $getResponseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleRequest($getResponseEvent);

        self::assertTrue($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        self::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Tablet view', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $responseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        self::assertInstanceOf(Response::class, $modifiedResponse);
        self::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        self::assertSame('Tablet view', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleDeviceIsTabletAsMobileAndTabletRedirectIsDisabledAndDetectTabletAsMobileIsTrue(): void
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com', 'status_code' => Response::HTTP_FOUND];
        $this->config['detect_tablet_as_mobile'] = true;

        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(self::atLeastOnce())->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects(self::atLeastOnce())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $getResponseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleRequest($getResponseEvent);

        self::assertFalse($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://mobilehost.com/some/parameters?%s=%s',
            $this->switchParam,
            DeviceView::VIEW_MOBILE
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasTabletRedirectWithoutPath(): void
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(self::atLeastOnce())->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT_WITHOUT_PATH, 2)
        );
        $this->mobileDetector->expects(self::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $getResponseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleRequest($getResponseEvent);

        self::assertFalse($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('http://testsite.com?device_view=tablet', $response->getTargetUrl());
        self::assertSame(\sprintf(
            'http://testsite.com?%s=%s',
            $this->switchParam,
            DeviceView::VIEW_TABLET
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasTabletNoRedirect(): void
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(self::atLeastOnce())->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::NO_REDIRECT, 1)
        );
        $this->mobileDetector->expects(self::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $getResponseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleRequest($getResponseEvent);

        self::assertTrue($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        self::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Tablet view no redirect', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $responseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        self::assertInstanceOf(Response::class, $modifiedResponse);
        self::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        self::assertSame('Tablet view no redirect', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasMobileRedirect(): void
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(self::atLeastOnce())->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects(self::once())->method('isTablet')->willReturn(false);
        $this->mobileDetector->expects(self::once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $getResponseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleRequest($getResponseEvent);

        self::assertFalse($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://testsite.com/some/parameters?%s=%s',
            $this->switchParam,
            DeviceView::VIEW_MOBILE
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasMobileRedirectWithoutPath(): void
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(self::atLeastOnce())->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT_WITHOUT_PATH, 2)
        );
        $this->mobileDetector->expects(self::once())->method('isTablet')->willReturn(false);
        $this->mobileDetector->expects(self::once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $getResponseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleRequest($getResponseEvent);

        self::assertFalse($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://testsite.com?%s=%s',
            $this->switchParam,
            DeviceView::VIEW_MOBILE
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasMobileNoRedirect(): void
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123];

        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(self::atLeastOnce())->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::NO_REDIRECT, 1)
        );
        $this->mobileDetector->expects(self::once())->method('isTablet')->willReturn(false);
        $this->mobileDetector->expects(self::once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $getResponseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleRequest($getResponseEvent);

        self::assertTrue($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        self::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Mobile view no redirect', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $responseEvent->getRequest()->headers->set('user-agent', 'agent');
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        self::assertInstanceOf(Response::class, $modifiedResponse);
        self::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        self::assertSame('Mobile view no redirect', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestUpdatedMobileDetectorUserAgent(): void
    {
        $this->mobileDetector->expects(self::once())->method('setUserAgent')->with(self::equalTo('agent'));

        $event = $this->createGetResponseEvent('some content');
        $event->getRequest()->headers->set('user-agent', 'agent');

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);
        $listener->handleRequest($event);
    }

    private function createGetResponseEvent(string $content, array $headers = []): ViewEvent
    {
        $event = new ViewEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            \defined('Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST') ? \constant('Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST') : \constant('Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST'),
            $content
        );
        $event->getRequest()->headers = new HeaderBag($headers);

        return $event;
    }

    private function createRouteCollectionWithRouteAndRoutingOption(string $returnValue, int $times): RouteCollection
    {
        $route = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->getMock();
        $route->expects(self::exactly($times))->method('getOption')->willReturn($returnValue);
        /**
         * @var MockObject|RouteCollection
         */
        $routeCollection = $this->createMock(RouteCollection::class);
        $routeCollection->expects(self::exactly($times))->method('get')->willReturn($route);

        return $routeCollection;
    }

    private function createResponseEvent(Response $response, array $headers = []): ResponseEvent
    {
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            \defined('Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST') ? \constant('Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST') : \constant('Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST'),
            $response
        );
        $event->getRequest()->headers = new HeaderBag($headers);

        return $event;
    }
}
