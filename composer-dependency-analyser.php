<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    // No class of symfony/yaml is referenced in PHP code, but the extension loads
    // Resources/config/services.yaml with it at runtime.
    ->ignoreErrorsOnPackage('symfony/yaml', [ErrorType::UNUSED_DEPENDENCY])
    // DeviceDataCollector::getData() declares the array|Data type of DataCollector::$data from
    // symfony/http-kernel; like http-kernel, the bundle never needs var-dumper to be installed
    // (PHP does not load a class named only in a return type).
    ->ignoreErrorsOnPackage('symfony/var-dumper', [ErrorType::SHADOW_DEPENDENCY])
;
