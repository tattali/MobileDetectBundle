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
        $cookie = Cookie::create('device_view', 'mobile');
        $response = new RedirectResponseWithCookie('http://testsite.com/', Response::HTTP_MOVED_PERMANENTLY, $cookie);

        self::assertSame('http://testsite.com/', $response->getTargetUrl());
        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        self::assertSame([$cookie], $response->headers->getCookies());
    }

    public function testNullStatusUsesTheDefaultRedirectStatus(): void
    {
        $response = new RedirectResponseWithCookie('http://testsite.com/', null, Cookie::create('device_view', 'mobile'));

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }
}
