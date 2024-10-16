<?php

declare(strict_types=1);

namespace MobileDetectBundle\Tests\Helper;

use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Helper\DeviceViewInterface;
use MobileDetectBundle\Helper\RedirectResponseWithCookie;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass
 */
final class DeviceViewTest extends TestCase
{
    /**
     * @var MockObject|RequestStack
     */
    private MockObject $requestStack;

    /**
     * @var MockObject|Request
     */
    private MockObject $request;

    private $cookieKey = DeviceViewInterface::COOKIE_KEY_DEFAULT;
    private $switchParam = DeviceViewInterface::SWITCH_PARAM_DEFAULT;

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
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceViewInterface::VIEW_MOBILE, $deviceView->getViewType());
        self::assertSame(DeviceViewInterface::VIEW_MOBILE, $deviceView->getRequestedViewType());
    }

    public function testGetViewTypeTablet(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceViewInterface::VIEW_TABLET, $deviceView->getViewType());
        self::assertSame(DeviceViewInterface::VIEW_TABLET, $deviceView->getRequestedViewType());
    }

    public function testGetViewTypeFull(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceViewInterface::VIEW_FULL, $deviceView->getViewType());
        self::assertSame(DeviceViewInterface::VIEW_FULL, $deviceView->getRequestedViewType());
    }

    public function testGetViewTypeNotMobile(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView();
        self::assertSame(DeviceViewInterface::VIEW_NOT_MOBILE, $deviceView->getViewType());
        self::assertNull($deviceView->getRequestedViewType());
    }

    public function testGetViewTypeMobileFromCookie(): void
    {
        $this->request->cookies = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceViewInterface::VIEW_MOBILE, $deviceView->getViewType());
        self::assertSame(DeviceViewInterface::VIEW_MOBILE, $deviceView->getRequestedViewType());
    }

    public function testIsFullViewTrue(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertTrue($deviceView->isFullView());
    }

    public function testIsFullViewFalse(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertFalse($deviceView->isFullView());
    }

    public function testIsTabletViewTrue(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertTrue($deviceView->isTabletView());
    }

    public function testIsTabletViewFalse(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertFalse($deviceView->isTabletView());
    }

    public function testIsMobileViewTrue(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertTrue($deviceView->isMobileView());
    }

    public function testIsMobileViewFalse(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertFalse($deviceView->isMobileView());
    }

    public function testIsNotMobileViewTrue(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_NOT_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertTrue($deviceView->isNotMobileView());
    }

    public function testIsNotMobileViewFalse(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertFalse($deviceView->isNotMobileView());
    }

    public function testHasSwitchParamTrue(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_MOBILE]);
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
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_MOBILE]);
        $deviceView = new DeviceView();
        self::assertFalse($deviceView->hasSwitchParam());
    }

    public function testSetViewMobile(): void
    {
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setView(DeviceViewInterface::VIEW_MOBILE);
        self::assertTrue($deviceView->isMobileView());
    }

    public function testSetViewFull(): void
    {
        $deviceView = new DeviceView();
        $deviceView->setView(DeviceViewInterface::VIEW_FULL);
        self::assertTrue($deviceView->isFullView());
    }

    public function testSetFullViewAndCheckIsFullView(): void
    {
        $deviceView = new DeviceView();
        $deviceView->setFullView();
        self::assertTrue($deviceView->isFullView());
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

    public function testGetSwitchParamValueFullDefault(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceViewInterface::VIEW_FULL, $deviceView->getSwitchParamValue());
    }

    public function testGetSwitchParamValueFull(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceViewInterface::VIEW_FULL, $deviceView->getSwitchParamValue());
    }

    public function testGetSwitchParamValueMobile(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceViewInterface::VIEW_MOBILE, $deviceView->getSwitchParamValue());
    }

    public function testGetSwitchParamValueTablet(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        self::assertSame(DeviceViewInterface::VIEW_TABLET, $deviceView->getSwitchParamValue());
    }

    public function testGetRedirectResponseBySwitchParamWithCookieViewMobile(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceViewInterface::VIEW_MOBILE => ['status_code' => Response::HTTP_MOVED_PERMANENTLY]]);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testGetRedirectResponseBySwitchParamWithCookieViewTablet(): void
    {
        $this->request->query = new InputBag([$this->switchParam => DeviceViewInterface::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceViewInterface::VIEW_TABLET => ['status_code' => Response::HTTP_MOVED_PERMANENTLY]]);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testGetRedirectResponseBySwitchParamWithCookieViewFullDefault(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testModifyResponseToMobileAndCheckResponse(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = new Response();
        self::assertCount(0, $response->headers->getCookies());
        $deviceView->modifyResponse(DeviceViewInterface::VIEW_MOBILE, $response);

        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceViewInterface::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testSetCookieKey(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setCookieKey('newCookieKey');

        self::assertSame('newCookieKey', $deviceView->getCookieKey());
    }

    public function testGetRedirectResponseWithCookieViewMobile(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = $deviceView->getRedirectResponse(DeviceViewInterface::VIEW_MOBILE, 'http://mobilesite.com', Response::HTTP_FOUND);
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        self::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            self::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                self::assertSame(DeviceViewInterface::VIEW_MOBILE, $cookie->getValue());
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

        $response = $deviceView->getRedirectResponse(DeviceViewInterface::VIEW_MOBILE, 'http://mobilesite.com', Response::HTTP_FOUND);
        self::assertInstanceOf(RedirectResponseWithCookie::class, $response);
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
    public function testSetCookieExpireDatetimeModifier(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setCookieExpireDatetimeModifier('+5 days');
        self::assertSame('+5 days', $deviceView->getCookieExpireDatetimeModifier());
    }

    public function testSetCookieRawTrue(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setCookieRaw(true);
        self::assertTrue($deviceView->isCookieRaw());
    }

    public function testSetCookieRawFalse(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setCookieRaw(false);
        self::assertFalse($deviceView->isCookieRaw());
    }

    public function testSetCookieSameSiteStrict(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setCookieSameSite('Strict');
        self::assertSame('Strict', $deviceView->getCookieSameSite());
    }

    public function testSetCookieSameSiteLax(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setCookieSameSite('Lax');
        self::assertSame('Lax', $deviceView->getCookieSameSite());
    }

    public function testSetCookieSameSiteNone(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setCookieSameSite('None');
        self::assertSame('None', $deviceView->getCookieSameSite());
    }

    public function testSetCookieSameSiteNull(): void
    {
        $this->request->query = new InputBag();
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setCookieSameSite(null);
        self::assertNull($deviceView->getCookieSameSite());
    }
}
