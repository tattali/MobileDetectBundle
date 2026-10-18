<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    // No class of these packages is referenced in PHP code, but the bundle needs them at runtime:
    // Resources/config/services.yaml injects the "router" and "request_stack" services that
    // FrameworkBundle defines, and the extension loads that file with symfony/yaml.
    ->ignoreErrorsOnPackage('symfony/framework-bundle', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/yaml', [ErrorType::UNUSED_DEPENDENCY])
;
