<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Detection\MobileDetect;
use MobileDetectBundle\DataCollector\DeviceDataCollector;
use MobileDetectBundle\EventListener\RequestResponseListener;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Twig\Extension\MobileDetectExtension;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('mobile_detect.cookie_expire_datetime_modifier', null)
        ->set('mobile_detect.cookie_key', null)
        ->set('mobile_detect.redirect', null)
        ->set('mobile_detect.switch_device_view.save_referer_path', null)
        ->set('mobile_detect.switch_param', null)
    ;

    $container->services()
        ->set(RequestResponseListener::class)
            ->args([
                service('mobile_detect.mobile_detector.default'),
                service('mobile_detect.device_view'),
                service('router'),
                param('mobile_detect.redirect'),
                param('mobile_detect.switch_device_view.save_referer_path'),
            ])
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'handleRequest', 'priority' => 1])
            ->tag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'handleResponse'])

        ->alias('mobile_detect.request_listener', RequestResponseListener::class)

        ->set(DeviceDataCollector::class)
            ->args([
                service('mobile_detect.device_view'),
                [param('mobile_detect.redirect')],
            ])
            ->tag('data_collector', [
                'id' => 'device.collector',
            ])

        ->alias('mobile_detect_bundle.device.collector', DeviceDataCollector::class)

        ->set(DeviceView::class)
            ->args([
                service('request_stack'),
                [param('mobile_detect.redirect')],
            ])
            ->call('setCookieDomain', [param('mobile_detect.cookie_domain')])
            ->call('setCookieExpireDatetimeModifier', [param('mobile_detect.cookie_expire_datetime_modifier')])
            ->call('setCookieHttpOnly', [param('mobile_detect.cookie_httpOnly')])
            ->call('setCookieKey', [param('mobile_detect.cookie_key')])
            ->call('setCookiePath', [param('mobile_detect.cookie_path')])
            ->call('setCookieSecure', [param('mobile_detect.cookie_secure')])
            ->call('setSwitchParam', [param('mobile_detect.switch_param')])

        ->alias('mobile_detect.device_view', DeviceView::class)

        ->set(MobileDetectExtension::class)
            ->args([
                service('request_stack'),
                service('mobile_detect.mobile_detector.default'),
                service('mobile_detect.device_view'),
                param('mobile_detect.redirect'),
            ])
            ->tag('twig.extension')

        ->alias('mobile_detect.twig.extension', MobileDetectExtension::class)

        ->set(MobileDetect::class)

        ->alias('mobile_detect.mobile_detector.default', MobileDetect::class)
    ;
};
