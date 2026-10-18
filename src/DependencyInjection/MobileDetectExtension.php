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

namespace MobileDetectBundle\DependencyInjection;

use MobileDetectBundle\EventListener\RequestResponseListener;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

class MobileDetectExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        // Disable redirections whose host is not a valid URL. A host read from an
        // env var (%env(...)%) is only known at runtime, so it is checked there.
        foreach ([RequestResponseListener::MOBILE, RequestResponseListener::TABLET, RequestResponseListener::DESKTOP] as $view) {
            $host = $config['redirect'][$view]['host'];
            if ($config['redirect'][$view]['is_enabled'] && !$this->usesEnvVar($container, $host) && !$this->validHost($host)) {
                $config['redirect'][$view]['is_enabled'] = false;
            }
        }

        $container->setParameter('mobile_detect.redirect', $config['redirect']);
        $container->setParameter('mobile_detect.switch_device_view.save_referer_path', $config['switch_device_view']['save_referer_path']);

        $container->setParameter('mobile_detect.cookie_key', $config['cookie_key']);
        $container->setParameter('mobile_detect.cookie_path', $config['cookie_path']);
        $container->setParameter('mobile_detect.cookie_domain', $config['cookie_domain']);
        $container->setParameter('mobile_detect.cookie_secure', $config['cookie_secure']);
        $container->setParameter('mobile_detect.cookie_httpOnly', $config['cookie_httpOnly']);
        $container->setParameter('mobile_detect.cookie_expire_datetime_modifier', $config['cookie_expire_datetime_modifier']);
        $container->setParameter('mobile_detect.switch_param', $config['switch_param']);
    }

    protected function usesEnvVar(ContainerBuilder $container, ?string $value): bool
    {
        if (null === $value) {
            return false;
        }

        $usedEnvs = [];
        $container->resolveEnvPlaceholders($value, null, $usedEnvs);

        return [] !== $usedEnvs;
    }

    protected function validHost(?string $url): bool
    {
        return (bool) filter_var($url, \FILTER_VALIDATE_URL);
    }
}
