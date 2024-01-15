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

namespace MobileDetectBundle\DeviceDetector;

use Detection\MobileDetect;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 */
class MobileDetector extends MobileDetect implements MobileDetectorInterface
{
    public const VERSION_TYPE_STRING = 'text';

    public const VERSION_TYPE_FLOAT = 'float';

    public static function getUserAgents(): array
    {
        return self::getBrowsers();
    }

    public function getCfHeaders(): array
    {
        return $this->getCloudFrontHttpHeaders();
    }
}
