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

namespace MobileDetectBundle\Tests\Helper;

use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Helper\RedirectResponseWithCookie;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
final class RedirectResponseWithCookieTest extends TestCase
{
    public function testItRedirectsWithTheCookie(): void
    {
        $cookie = self::createCookie();
        $response = new RedirectResponseWithCookie('http://testsite.com/', Response::HTTP_MOVED_PERMANENTLY, $cookie);

        self::assertSame('http://testsite.com/', $response->getTargetUrl());
        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        self::assertSame([$cookie], $response->headers->getCookies());
    }

    public function testNullStatusUsesTheDefaultRedirectStatus(): void
    {
        $response = new RedirectResponseWithCookie('http://testsite.com/', null, self::createCookie());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /**
     * A cookie with the bundle's default path and domain: older Symfony 7 minors (7.2 here) store
     * cookies by domain, and a null domain is a deprecated array offset on PHP 8.5.
     */
    private static function createCookie(): Cookie
    {
        return Cookie::create(DeviceView::COOKIE_KEY_DEFAULT, DeviceView::VIEW_MOBILE, 0, DeviceView::COOKIE_PATH_DEFAULT, DeviceView::COOKIE_DOMAIN_DEFAULT);
    }
}
