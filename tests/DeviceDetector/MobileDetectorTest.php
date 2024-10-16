<?php

namespace MobileDetectBundle\Tests\DeviceDetector;

use MobileDetectBundle\DeviceDetector\MobileDetector;
use PHPUnit\Framework\TestCase;

/**
 * MobileDetectorTest class.
 *
 * Tests the functionality of the MobileDetector class.
 *
 * @internal
 *
 * @coversNothing
 */
final class MobileDetectorTest extends TestCase
{
    /**
     * Tests the getUserAgents method of the MobileDetector class.
     */
    public function testGetUserAgents(): void
    {
        $userAgents = MobileDetector::getUserAgents();

        // Assert that the method returns an array.
        self::assertIsArray($userAgents);

        // Assert that the array is not empty.
        self::assertNotEmpty($userAgents);

        // Assert that all elements in the array are strings.
        foreach ($userAgents as $userAgent) {
            self::assertIsString($userAgent);
        }
    }

    /**
     * Tests the getCfHeaders method of the MobileDetector class.
     */
    public function testGetCfHeaders(): void
    {
        $mobileDetector = new MobileDetector();
        $cfHeaders = $mobileDetector->getCfHeaders();

        // Assert that the method returns an array.
        self::assertIsArray($cfHeaders);
    }
}
