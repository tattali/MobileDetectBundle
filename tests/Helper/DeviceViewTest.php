<?php

declare(strict_types=1);

namespace MobileDetectBundle\Tests\Helper;

use MobileDetectBundle\Helper\DeviceView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

final class DeviceViewTest extends TestCase
{
    private MockObject&RequestStack $requestStack;

    private MockObject&Request $request;

    private string $cookieKey = DeviceView::COOKIE_KEY_DEFAULT;

    private string $switchParam = DeviceView::SWITCH_PARAM_DEFAULT;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(Request::class)->getMock();
        $this->request->expects(self::any())->method('getSchemeAndHttpHost')->willReturn('http://testsite.com');
        $this->request->expects(self::any())->method('getUriForPath')->willReturn('/');
        $this->request->query = new InputBag();
        $this->request->cookies = new InputBag();

        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request)
        ;
    }

    public function testGetViewTypeMobile(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getRequestedViewType());
    }

    public function testGetViewTypeTablet(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getRequestedViewType());
    }

    public function testGetViewTypeDesktop(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_DESKTOP]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceView::VIEW_DESKTOP, $deviceView->getViewType());
        self::assertSame(DeviceView::VIEW_DESKTOP, $deviceView->getRequestedViewType());
    }

    public function testGetViewTypeNotMobile(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView();
        self::assertSame(DeviceView::VIEW_NOT_MOBILE, $deviceView->getViewType());
        self::assertNull($deviceView->getRequestedViewType());
    }

    public function testGetViewTypeMobileFromCookie(): void
    {
        $this->request->cookies = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getRequestedViewType());
    }

    public function testIsDesktopViewTrue(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_DESKTOP]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertTrue($deviceView->isDesktopView());
    }

    public function testIsDesktopViewFalse(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertFalse($deviceView->isDesktopView());
    }

    public function testIsTabletViewTrue(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertTrue($deviceView->isTabletView());
    }

    public function testIsTabletViewFalse(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertFalse($deviceView->isTabletView());
    }

    public function testIsMobileViewTrue(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertTrue($deviceView->isMobileView());
    }

    public function testIsMobileViewFalse(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertFalse($deviceView->isMobileView());
    }

    public function testIsNotMobileViewTrue(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_NOT_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertTrue($deviceView->isNotMobileView());
    }

    public function testIsNotMobileViewFalse(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertFalse($deviceView->isNotMobileView());
    }

    public function testHasSwitchParamTrue(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertTrue($deviceView->hasSwitchParam());
    }

    public function testHasSwitchParamFalse1(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        self::assertFalse($deviceView->hasSwitchParam());
    }

    public function testHasSwitchParamFalse2(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView();
        self::assertFalse($deviceView->hasSwitchParam());
    }

    public function testSetViewMobile(): void
    {
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setView(DeviceView::VIEW_MOBILE);
        self::assertTrue($deviceView->isMobileView());
    }

    public function testSetViewDesktop(): void
    {
        $deviceView = new DeviceView();
        $deviceView->setView(DeviceView::VIEW_DESKTOP);
        self::assertTrue($deviceView->isDesktopView());
    }

    public function testSetDesktopViewAndCheckIsDesktopView(): void
    {
        $deviceView = new DeviceView();
        $deviceView->setDesktopView();
        self::assertTrue($deviceView->isDesktopView());
    }

    public function testSetTabletViewAndCheckIsTabletView(): void
    {
        $deviceView = new DeviceView();
        $deviceView->setTabletView();
        self::assertTrue($deviceView->isTabletView());
    }

    public function testSetMobileViewAndCheckIsMobileView(): void
    {
        $deviceView = new DeviceView();
        $deviceView->setMobileView();
        self::assertTrue($deviceView->isMobileView());
    }

    public function testSetNotMobileViewAndCheckIsNotMobileView(): void
    {
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setNotMobileView();
        self::assertTrue($deviceView->isNotMobileView());
    }

    public function testGetSwitchParamValueNull(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView();
        self::assertNull($deviceView->getSwitchParamValue());
    }

    public function testGetSwitchParamValueDesktopDefault(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceView::VIEW_DESKTOP, $deviceView->getSwitchParamValue());
    }

    public function testGetSwitchParamValueDesktop(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_DESKTOP]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceView::VIEW_DESKTOP, $deviceView->getSwitchParamValue());
    }

    public function testGetSwitchParamValueMobile(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getSwitchParamValue());
    }

    public function testGetSwitchParamValueTablet(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceView::VIEW_TABLET, $deviceView->getSwitchParamValue());
    }

    public function testGetRedirectResponseBySwitchParamWithCookieViewMobile(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack, [DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_MOVED_PERMANENTLY]]);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testGetRedirectResponseBySwitchParamWithCookieViewTablet(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack, [DeviceView::VIEW_TABLET => ['status_code' => Response::HTTP_MOVED_PERMANENTLY]]);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testGetRedirectResponseBySwitchParamWithCookieViewDesktopDefault(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testModifyResponseToMobileAndCheckResponse(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = new Response();
        self::assertCount(0, $response->headers->getCookies());
        $response = $deviceView->modifyResponse(DeviceView::VIEW_MOBILE, $response);

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testGetRedirectResponseWithCookieViewMobile(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = $deviceView->getRedirectResponse(DeviceView::VIEW_MOBILE, 'http://mobilesite.com', Response::HTTP_FOUND);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testGetRedirectResponseAndCheckCookieSettings(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setCookiePath('/test');
        $deviceView->setCookieDomain('example.com');
        $deviceView->setCookieSecure(true);
        $deviceView->setCookieHttpOnly(false);

        $response = $deviceView->getRedirectResponse(DeviceView::VIEW_MOBILE, 'http://mobilesite.com', Response::HTTP_FOUND);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());

        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);
        self::assertSame('/test', $cookies[0]->getPath());
        self::assertSame('example.com', $cookies[0]->getDomain());
        self::assertTrue($cookies[0]->isSecure());
        self::assertFalse($cookies[0]->isHttpOnly());
    }

    public function testGetCookieKeyDeviceView(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame($this->cookieKey, $deviceView->getCookieKey());
    }

    public function testGetSwitchParamDeviceView(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame($this->switchParam, $deviceView->getSwitchParam());
    }
}
