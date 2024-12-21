<?php

declare(strict_types=1);

namespace MobileDetectBundle\Tests\Twig\Extension;

use Detection\MobileDetect;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Twig\Extension\MobileDetectExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

final class MobileDetectExtensionTest extends TestCase
{
    private MockObject&MobileDetect $mobileDetect;

    private MockObject&RequestStack $requestStack;

    private MockObject&Request $request;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    private string $switchParam = DeviceView::SWITCH_PARAM_DEFAULT;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mobileDetect = $this->createMock(MobileDetect::class);
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

        $this->config = [
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ];
    }

    public function testGetFunctionsArray(): void
    {
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);

        $functions = $extension->getFunctions();
        self::assertCount(13, $functions);
        $names = [
            'is_mobile' => 'isMobile',
            'is_tablet' => 'isTablet',
            'is_device' => 'isDevice',
            'is_full_view' => 'isFullView',
            'is_mobile_view' => 'isMobileView',
            'is_tablet_view' => 'isTabletView',
            'is_not_mobile_view' => 'isNotMobileView',
            'is_ios' => 'isiOS',
            'is_android_os' => 'isAndroidOS',
            'is_windows_os' => 'isWindowsOS',
            'full_view_url' => 'fullViewUrl',
            'device_version' => 'deviceVersion',
            'rules_list' => 'getRules',
        ];
        foreach ($functions as $function) {
            $name = $function->getName();
            $callable = $function->getCallable();
            self::assertArrayHasKey($name, $names);
            self::assertIsArray($callable);
            self::assertSame($names[$name], $callable[1]);
        }
    }

    public function testRulesList(): void
    {
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, new MobileDetect(), $deviceView, $this->config);
        self::assertEqualsWithDelta(173, \count($extension->getRules()), 190);
    }

    public function testDeviceVersion(): void
    {
        $reflection = new \ReflectionClass(MobileDetect::class);
        $versionTypeString = $reflection->getConstant('VERSION_TYPE_STRING');
        $versionTypeFloat = $reflection->getConstant('VERSION_TYPE_FLOAT');

        $matcher = self::exactly(3);
        $this->mobileDetect->expects($matcher)
            ->method('version')
            ->willReturnCallback(static function ($parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    self::assertSame('Version', $parameters);

                    return false;
                }

                if (2 === $matcher->numberOfInvocations()) {
                    self::assertSame('Firefox', $parameters);

                    return '98.0';
                }

                if (3 === $matcher->numberOfInvocations()) {
                    self::assertSame('Firefox', $parameters);

                    return 98.0;
                }
            })
        ;

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertNull($extension->deviceVersion('Version', $versionTypeString));
        self::assertSame('98.0', $extension->deviceVersion('Firefox', $versionTypeString));
        self::assertSame(98.0, $extension->deviceVersion('Firefox', $versionTypeFloat));
    }

    public function testFullViewUrlHostNull(): void
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => null];

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertNull($extension->fullViewUrl());
    }

    public function testFullViewUrlHostEmpty(): void
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => ''];

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertNull($extension->fullViewUrl());
    }

    public function testFullViewUrlNotSetRequest(): void
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertSame('http://mobilehost.com', $extension->fullViewUrl());
    }

    public function testFullViewUrlWithRequestQuery(): void
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->request->query = new InputBag(['myparam' => 'myvalue']);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertSame('http://mobilehost.com?myparam=myvalue', $extension->fullViewUrl());
    }

    public function testFullViewUrlWithRequestOnlyHost(): void
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->request->query = new InputBag(['myparam' => 'myvalue']);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertSame('http://mobilehost.com', $extension->fullViewUrl(false));
    }

    public function testIsMobileTrue(): void
    {
        $this->mobileDetect->expects(self::once())->method('isMobile')->willReturn(true);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertTrue($extension->isMobile());
    }

    public function testIsMobileFalse(): void
    {
        $this->mobileDetect->expects(self::once())->method('isMobile')->willReturn(false);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertFalse($extension->isMobile());
    }

    public function testIsTabletTrue(): void
    {
        $this->mobileDetect->expects(self::once())->method('isTablet')->willReturn(true);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertTrue($extension->isTablet());
    }

    public function testIsTabletFalse(): void
    {
        $this->mobileDetect->expects(self::once())->method('isTablet')->willReturn(false);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertFalse($extension->isTablet());
    }

    public function testIsDeviceIPhone(): void
    {
        $this->mobileDetect->expects(self::once())
            ->method('__call')
            ->with(self::equalTo('isiphone'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertTrue($extension->isDevice('iphone'));
    }

    public function testIsDeviceAndroid(): void
    {
        $this->mobileDetect->expects(self::once())
            ->method('__call')
            ->with(self::equalTo('isandroid'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertTrue($extension->isDevice('android'));
    }

    public function testIsFullViewTrue(): void
    {
        $this->request->cookies = new InputBag([$this->switchParam => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertTrue($extension->isFullView());
    }

    public function testIsFullViewFalse(): void
    {
        $deviceView = new DeviceView();
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertFalse($extension->isFullView());
    }

    public function testIsMobileViewTrue(): void
    {
        $this->request->cookies = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertTrue($extension->isMobileView());
    }

    public function testIsMobileViewFalse(): void
    {
        $this->request->cookies = new InputBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertFalse($extension->isMobileView());
    }

    public function testIsTabletViewTrue(): void
    {
        $this->request->cookies = new InputBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertTrue($extension->isTabletView());
    }

    public function testIsTabletViewFalse(): void
    {
        $this->request->cookies = new InputBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertFalse($extension->isTabletView());
    }

    public function testIsNotMobileViewTrue(): void
    {
        $this->request->cookies = new InputBag([$this->switchParam => DeviceView::VIEW_NOT_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertTrue($extension->isNotMobileView());
    }

    public function testIsNotMobileViewFalse(): void
    {
        $this->request->cookies = new InputBag([$this->switchParam => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertFalse($extension->isNotMobileView());
    }

    public function testIsiOSTrue(): void
    {
        $this->mobileDetect->expects(self::once())
            ->method('__call')
            ->with(self::equalTo('isiOS'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertTrue($extension->isiOS());
    }

    public function testIsiOSFalse(): void
    {
        $this->mobileDetect->expects(self::once())
            ->method('__call')
            ->with(self::equalTo('isiOS'))
            ->willReturn(false)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertFalse($extension->isiOS());
    }

    public function testIsAndroidOSTrue(): void
    {
        $this->mobileDetect->expects(self::once())
            ->method('__call')
            ->with(self::equalTo('isAndroidOS'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertTrue($extension->isAndroidOS());
    }

    public function testIsAndroidOSFalse(): void
    {
        $this->mobileDetect->expects(self::once())
            ->method('__call')
            ->with(self::equalTo('isAndroidOS'))
            ->willReturn(false)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertFalse($extension->isAndroidOS());
    }

    public function testIsWindowsOSTrue(): void
    {
        $matcher = self::exactly(1);
        $this->mobileDetect->expects($matcher)
            ->method('__call')
            ->willReturnCallback(static function ($parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    self::assertSame('isWindowsMobileOS', $parameters);

                    return true;
                }

                // if (1 === $matcher->numberOfInvocations()) {
                //     self::assertSame('isWindowsPhoneOS', $parameters);

                //     return true;
                // }
            })
        ;

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertTrue($extension->isWindowsOS());
    }

    public function testIsWindowsOSFalse(): void
    {
        $matcher = self::exactly(2);
        $this->mobileDetect->expects($matcher)
            ->method('__call')
            ->willReturnCallback(static function ($parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    self::assertSame('isWindowsMobileOS', $parameters);

                    return false;
                }

                // if (1 === $matcher->numberOfInvocations()) {
                //     self::assertSame('isWindowsPhoneOS', $parameters);

                //     return false;
                // }
            })
        ;

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetect, $deviceView, $this->config);
        self::assertFalse($extension->isWindowsOS());
    }
}
