<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php82: true)
    ->withAttributesSets(symfony: true, doctrine: true)
    ->withSets([
        Rector\Symfony\Set\SymfonySetList::SYMFONY_50,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_50_TYPES,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_51,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_52,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_52_VALIDATOR_ATTRIBUTES,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_53,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_60,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_61,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_62,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_63,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_64,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_CODE_QUALITY,
        Rector\Symfony\Set\SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ])
    ->withDeadCodeLevel(10)
    ->withTypeCoverageLevel(10);
