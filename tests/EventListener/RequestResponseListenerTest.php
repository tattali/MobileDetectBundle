<?php

declare(strict_types=1);

namespace MobileDetectBundle\Tests\EventListener;

use Detection\MobileDetect;
use MobileDetectBundle\DependencyInjection\Configuration;
use MobileDetectBundle\EventListener\RequestResponseListener;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Helper\RedirectResponseWithCookie;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @phpstan-import-type RedirectConfig from Configuration
 */
final class RequestResponseListenerTest extends TestCase
{
    private MockObject&MobileDetect $mobileDetect;

    private MockObject&RequestStack $requestStack;

    private MockObject&Request $request;

    private MockObject&RouterInterface $router;

    /**
     * @var RedirectConfig
     */
    private array $config;

    private string $switchParam = DeviceView::SWITCH_PARAM_DEFAULT;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mobileDetect = $this->createMock(MobileDetect::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->request = $this->getMockBuilder(Request::class)->getMock();
        $this->request->expects(self::any())->method('getSchemeAndHttpHost')->willReturn('http://testhost.com');
        // Request::get() is deprecated since Symfony 7.4: the route name must be read from the attributes
        $this->request->expects(self::never())->method('get');
        $this->request->expects(self::any())->method('getUriForPath')->willReturn('/');
        $this->request->attributes = new ParameterBag(['_route' => 'value']);
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
        $deviceView = new DeviceView($this->requestStack, [DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_FOUND]]);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, []);
        $event = $this->createGetResponseEvent('some content');

        $listener->handleRequest($event);
        self::assertFalse($listener->needsResponseModification());

        /** @var ?RedirectResponseWithCookie */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestBis(): void
    {
        $this->request->query = new InputBag(['myparam' => 'myvalue', $this->switchParam => DeviceView::VIEW_MOBILE]);
        $this->request->expects(self::any())->method('getPathInfo')->willReturn('/');
        $deviceView = new DeviceView($this->requestStack, [DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_FOUND]]);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, [], false);
        $event = $this->createGetResponseEvent('some content');

        $listener->handleRequest($event);
        self::assertFalse($listener->needsResponseModification());

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
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
        $this->router->expects(self::exactly(2))->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2),
        );

        $deviceView = new DeviceView($this->requestStack, [DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_FOUND]]);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);
        $event = $this->createGetResponseEvent('some content');

        $listener->handleRequest($event);
        self::assertFalse($listener->needsResponseModification());

        /** @var ?RedirectResponseWithCookie */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://mobilehost.com/?%s=%s&myparam=myvalue',
            $this->switchParam,
            DeviceView::VIEW_MOBILE,
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestDoesNotRedirectToInvalidHost(): void
    {
        // A host coming from an env var is only known at runtime: when it does
        // not resolve to a valid URL, the listener must not redirect to it.
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'not a url', 'status_code' => Response::HTTP_FOUND];

        $this->mobileDetect->expects(self::any())->method('isMobile')->willReturn(true);
        $this->router->expects(self::any())->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 1),
        );

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);
        $event = $this->createGetResponseEvent('some content');

        $listener->handleRequest($event);

        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());
        self::assertNull($event->getResponse());
        self::assertTrue($listener->needsResponseModification());
    }

    public function testHandleRequestIsDesktopView(): void
    {
        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);
        self::assertFalse($deviceView->hasSwitchParam());
        self::assertNull($deviceView->getRequestedViewType());
        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        self::assertTrue($listener->needsResponseModification());
        self::assertSame(DeviceView::VIEW_DESKTOP, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        self::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Desktop view', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        self::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        self::assertSame('Desktop view', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_DESKTOP, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestIsNotMobileView(): void
    {
        $deviceView = new DeviceView();
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);
        self::assertFalse($deviceView->hasSwitchParam());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_NOT_MOBILE, $deviceView->getViewType());
        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        self::assertFalse($listener->needsResponseModification());

        $requestEventResponse = $getResponseEvent->getResponse();
        self::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Not mobile view', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

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
        $this->router->expects(self::exactly(2))->method('getRouteCollection')->willReturn(
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2),
        );
        $this->mobileDetect->expects(self::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        self::assertFalse($deviceView->hasSwitchParam());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        /** @var ?RedirectResponseWithCookie */
        $response = $getResponseEvent->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://t.testsite.com/some/parameters?%s=%s&some=param',
            $this->switchParam,
            DeviceView::VIEW_TABLET,
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
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
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2),
        );
        $this->mobileDetect->expects(self::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setSwitchParam($switchParam);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        self::assertFalse($deviceView->hasSwitchParam());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        /** @var ?RedirectResponseWithCookie */
        $response = $getResponseEvent->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://testsite.com/some/parameters?%s=%s&some=param',
            $switchParam,
            DeviceView::VIEW_TABLET,
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleDeviceIsTabletAndTabletRedirectIsDisabledAndDetectTabletAsMobileIsFalse(): void
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->mobileDetect->expects(self::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        self::assertTrue($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        self::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Tablet view', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        self::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        self::assertSame('Tablet view', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
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
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2),
        );
        $this->mobileDetect->expects(self::atLeastOnce())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        self::assertFalse($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        /** @var ?RedirectResponseWithCookie */
        $response = $getResponseEvent->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://mobilehost.com/some/parameters?%s=%s',
            $this->switchParam,
            DeviceView::VIEW_MOBILE,
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
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
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT_WITHOUT_PATH, 2),
        );
        $this->mobileDetect->expects(self::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        self::assertFalse($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        /** @var ?RedirectResponseWithCookie */
        $response = $getResponseEvent->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('http://testsite.com?device_view=tablet', $response->getTargetUrl());
        self::assertSame(\sprintf(
            'http://testsite.com?%s=%s',
            $this->switchParam,
            DeviceView::VIEW_TABLET,
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
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
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::NO_REDIRECT, 1),
        );
        $this->mobileDetect->expects(self::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        self::assertTrue($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        self::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Tablet view no redirect', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        self::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        self::assertSame('Tablet view no redirect', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
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
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2),
        );
        $this->mobileDetect->expects(self::once())->method('isTablet')->willReturn(false);
        $this->mobileDetect->expects(self::once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        self::assertFalse($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        /** @var ?RedirectResponseWithCookie */
        $response = $getResponseEvent->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://testsite.com/some/parameters?%s=%s',
            $this->switchParam,
            DeviceView::VIEW_MOBILE,
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
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
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::REDIRECT_WITHOUT_PATH, 2),
        );
        $this->mobileDetect->expects(self::once())->method('isTablet')->willReturn(false);
        $this->mobileDetect->expects(self::once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        self::assertFalse($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        /** @var ?RedirectResponseWithCookie */
        $response = $getResponseEvent->getResponse();
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(\sprintf(
            'http://testsite.com?%s=%s',
            $this->switchParam,
            DeviceView::VIEW_MOBILE,
        ), $response->getTargetUrl());

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
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
            $this->createRouteCollectionWithRouteAndRoutingOption(RequestResponseListener::NO_REDIRECT, 1),
        );
        $this->mobileDetect->expects(self::once())->method('isTablet')->willReturn(false);
        $this->mobileDetect->expects(self::once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        self::assertTrue($listener->needsResponseModification());
        self::assertNull($deviceView->getRequestedViewType());
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        self::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Mobile view no redirect', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        self::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        self::assertSame('Mobile view no redirect', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestUpdatedMobileDetectUserAgent(): void
    {
        $this->mobileDetect->expects(self::once())->method('setUserAgent')->with(self::equalTo('agent'));

        $event = $this->createGetResponseEvent('some content');
        $event->getRequest()->headers->set('user-agent', 'agent');

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);
        $listener->handleRequest($event);
    }

    public function testSwitchParamWithUnknownRedirectActionRedirectsToTheCurrentUrl(): void
    {
        $request = Request::create('http://testhost.com/page?'.$this->switchParam.'='.DeviceView::VIEW_MOBILE);
        $request->attributes->set('_route', 'page');
        $this->router->method('getRouteCollection')->willReturn(new RouteCollection());
        $config = $this->config;
        $config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'unknown'];

        $response = $this->handleRealRequest($request, $config);

        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame('http://testhost.com/page', $response->getTargetUrl());
    }

    public function testRequestWithoutRouteUsesTheConfiguredRedirectAction(): void
    {
        $request = Request::create('http://testhost.com/page');
        $this->mobileDetect->method('isMobile')->willReturn(true);
        $this->router->method('getRouteCollection')->willReturn(new RouteCollection());
        $config = $this->config;
        $config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com', 'status_code' => Response::HTTP_FOUND, 'action' => RequestResponseListener::REDIRECT];

        $response = $this->handleRealRequest($request, $config);

        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame('http://mobilehost.com/page?'.$this->switchParam.'='.DeviceView::VIEW_MOBILE, $response->getTargetUrl());
    }

    public function testSwitchParamWithoutValueRedirectsToTheCurrentUrl(): void
    {
        $request = Request::create('http://testhost.com/page');
        $request->query->set($this->switchParam, null);
        $request->attributes->set('_route', 'page');

        $response = $this->handleRealRequest($request, $this->config);

        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame('http://testhost.com/page', $response->getTargetUrl());
    }

    public function testCookieNamingAConfigKeyThatIsNotAViewDoesNotRedirect(): void
    {
        $request = Request::create('http://testhost.com/page', 'GET', [], [DeviceView::COOKIE_KEY_DEFAULT => 'detect_tablet_as_mobile']);
        $request->attributes->set('_route', 'page');

        $response = $this->handleRealRequest($request, $this->config);

        self::assertNull($response);
    }

    public function testEnabledViewWithoutHostDoesNotRedirect(): void
    {
        $request = Request::create('http://testhost.com/page');
        $request->attributes->set('_route', 'page');
        $this->mobileDetect->method('isMobile')->willReturn(true);
        $this->router->method('getRouteCollection')->willReturn(new RouteCollection());
        $config = $this->config;
        $config['mobile'] = ['is_enabled' => true, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => RequestResponseListener::REDIRECT];

        $response = $this->handleRealRequest($request, $config);

        self::assertNull($response);
    }

    /**
     * @param array<string, list<string|null>> $headers
     */
    private function createGetResponseEvent(string $content, array $headers = []): ViewEvent
    {
        $event = new ViewEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
            $content,
        );
        $event->getRequest()->headers = new HeaderBag($headers);

        return $event;
    }

    private function createRouteCollectionWithRouteAndRoutingOption(string $returnValue, int $times): MockObject&RouteCollection
    {
        $route = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->getMock();
        $route->expects(self::exactly($times))->method('getOption')->willReturn($returnValue);
        /**
         * @var MockObject&RouteCollection
         */
        $routeCollection = $this->createMock(RouteCollection::class);
        $routeCollection->expects(self::exactly($times))->method('get')->willReturn($route);

        return $routeCollection;
    }

    public function testHandleRequestWithoutTheTabletAsMobileSettingDetectsTabletsAsMobile(): void
    {
        $this->mobileDetect->expects(self::any())->method('isTablet')->willReturn(true);
        $this->mobileDetect->expects(self::any())->method('isMobile')->willReturn(true);
        $request = Request::create('http://testhost.com/page');
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $deviceView = new DeviceView($requestStack);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, []);

        $listener->handleRequest(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));

        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());
        self::assertTrue($listener->needsResponseModification());
    }

    public function testHandleRequestRedirectsWithFoundWhenTheViewHasNoStatusCode(): void
    {
        $this->mobileDetect->expects(self::any())->method('isMobile')->willReturn(true);
        $response = $this->handleRealRequest(Request::create('http://testhost.com/page'), [
            DeviceView::VIEW_MOBILE => ['is_enabled' => true, 'host' => 'http://m.testhost.com', 'action' => RequestResponseListener::REDIRECT],
            'detect_tablet_as_mobile' => false,
        ]);

        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('http://m.testhost.com/page?device_view=mobile', $response->getTargetUrl());
    }

    public function testHandleRequestWithADeviceViewThatSetsNoViewLeavesTheResponseAlone(): void
    {
        // a DeviceView subclass may leave the view unset after detection
        $deviceView = $this->createMock(DeviceView::class);
        $deviceView->expects(self::any())->method('getViewType')->willReturn(null);
        $deviceView->expects(self::any())->method('getRequestedViewType')->willReturn(null);
        $listener = new RequestResponseListener($this->mobileDetect, $deviceView, $this->router, $this->config);
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), Request::create('http://testhost.com/page'), HttpKernelInterface::MAIN_REQUEST);

        $listener->handleRequest($event);

        self::assertNull($event->getResponse());
        self::assertFalse($listener->needsResponseModification());
    }

    public function testGetRedirectUrlWithoutHost(): void
    {
        $request = Request::create('http://testhost.com/page');
        $request->attributes->set('_route', 'page');
        $routeCollection = new RouteCollection();
        $routeCollection->add('page', new Route('/page', [], [], [DeviceView::VIEW_MOBILE => RequestResponseListener::REDIRECT]));
        $routeCollection->add('home', new Route('/', [], [], [DeviceView::VIEW_MOBILE => RequestResponseListener::REDIRECT_WITHOUT_PATH]));
        $this->router->expects(self::any())->method('getRouteCollection')->willReturn($routeCollection);
        $listener = new class($this->mobileDetect, new DeviceView(), $this->router, $this->config) extends RequestResponseListener {
            public function redirectUrl(Request $request, string $view): ?string
            {
                return $this->getRedirectUrl($request, $view);
            }
        };

        self::assertNull($listener->redirectUrl($request, DeviceView::VIEW_MOBILE));

        $request->attributes->set('_route', 'home');
        self::assertSame('?device_view=mobile', $listener->redirectUrl($request, DeviceView::VIEW_MOBILE));
    }

    /**
     * @param array<string, list<string|null>> $headers
     */
    private function createResponseEvent(Response $response, array $headers = []): ResponseEvent
    {
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );
        $event->getRequest()->headers = new HeaderBag($headers);

        return $event;
    }

    /**
     * @param RedirectConfig $config
     */
    private function handleRealRequest(Request $request, array $config): ?Response
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $listener = new RequestResponseListener($this->mobileDetect, new DeviceView($requestStack, $config), $this->router, $config);
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener->handleRequest($event);

        return $event->getResponse();
    }
}
