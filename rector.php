<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__]);

    $rectorConfig->skip([
        __DIR__ . '/Assets',
        __DIR__ . '/Config',
        __DIR__ . '/Translations',
        __DIR__ . '/vendor',
        __DIR__ . '/rector.php',
        __DIR__ . '/.php-cs-fixer.php',
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_74,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE
    ]);
};
