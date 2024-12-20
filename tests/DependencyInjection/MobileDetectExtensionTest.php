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

namespace MobileDetectBundle\Tests\DependencyInjection;

use Detection\MobileDetect;
use MobileDetectBundle\DependencyInjection\MobileDetectExtension;
use MobileDetectBundle\Helper\DeviceView;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 *
 * @internal
 *
 * @coversDefaultClass
 */
final class MobileDetectExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var MobileDetectExtension
     */
    private $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder();
        $this->extension = new MobileDetectExtension();
    }

    public function testLoadDefaultConfig(): void
    {
        $config = [];
        $this->extension->load($config, $this->container);
        self::assertSame(
            [
                'mobile' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                'detect_tablet_as_mobile' => false,
            ],
            $this->container->getParameter('mobile_detect.redirect')
        );
        self::assertTrue($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
        self::assertSame(DeviceView::COOKIE_KEY_DEFAULT, $this->container->getParameter('mobile_detect.cookie_key'));
        self::assertSame(
            DeviceView::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT,
            $this->container->getParameter('mobile_detect.cookie_expire_datetime_modifier')
        );
        self::assertSame(
            DeviceView::SWITCH_PARAM_DEFAULT,
            $this->container->getParameter('mobile_detect.switch_param')
        );

        self::assertTrue($this->container->hasDefinition(MobileDetect::class));
    }

    public function testCustomRedirectConfigMobileHost(): void
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http://m.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        self::assertSame([
            'mobile' => ['is_enabled' => true, 'host' => 'http://m.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    public function testCustomRedirectConfigWithMobileNotValidHost(): void
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http:///m.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        self::assertSame([
            'mobile' => ['is_enabled' => false, 'host' => 'http:///m.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    public function testCustomRedirectConfigWithTabletNotValidHost(): void
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http://m.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => true, 'host' => 'http:///t.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        self::assertSame([
            'mobile' => ['is_enabled' => true, 'host' => 'http://m.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => 'http:///t.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    public function testCustomRedirectConfigWithFullNotValidHost(): void
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http://m.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => true, 'host' => 'http://t.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => 'http://testsite', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        self::assertSame([
            'mobile' => ['is_enabled' => true, 'host' => 'http://m.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => true, 'host' => 'http://t.testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => 'http://testsite', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    public function testCustomConfigSaveRefererPathTrue(): void
    {
        $config = [
            'mobile_detect' => [
                'switch_device_view' => [
                    'save_referer_path' => true,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        self::assertTrue($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
    }

    public function testCustomConfigSaveRefererPathFalse(): void
    {
        $config = [
            'mobile_detect' => [
                'switch_device_view' => [
                    'save_referer_path' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        self::assertFalse($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
    }

    public function testCustomConfigCookieKey(): void
    {
        $config = [
            'mobile_detect' => [
                'cookie_key' => 'custom_key',
            ],
        ];
        $this->extension->load($config, $this->container);
        self::assertSame('custom_key', $this->container->getParameter('mobile_detect.cookie_key'));
    }

    public function testCustomConfigCookieExpire(): void
    {
        $config = [
            'mobile_detect' => [
                'cookie_expire_datetime_modifier' => '6 month',
            ],
        ];
        $this->extension->load($config, $this->container);
        self::assertSame('6 month', $this->container->getParameter('mobile_detect.cookie_expire_datetime_modifier'));
    }

    public function testCustomConfigSwitchParam(): void
    {
        $config = [
            'mobile_detect' => [
                'switch_param' => 'switch_param_custom',
            ],
        ];
        $this->extension->load($config, $this->container);
        self::assertSame('switch_param_custom', $this->container->getParameter('mobile_detect.switch_param'));
    }
}
