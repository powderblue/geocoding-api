#!/usr/bin/env php
<?php

/**
 * System tests
 */

declare(strict_types=1);

use PowderBlue\GeocodingApi\Geocode;

require dirname(__DIR__) . '/vendor/autoload.php';

$methodArgLists = [
    'byAddress' => [
        ['25 Old Gardens Close Tunbridge Wells TN2 5ND', 'GB'],
        ['Les Houches', 'FR'],
        ['Chamonix - Les Houches', 'FR'],
        ['Chamonix - Centre', 'FR'],
    ],
    'byPostcode' => [
        ['07001', 'ES'],
        ['SW1E 5ND', 'GB'],
    ],
    'byLatLong' => [
        ['43.549543', '7.014364', 'FR'],
        ['39.513047', '2.538872', 'ES'],
        ['43.549543', '7.014364'],
        [43.549543, 7.014364],
        ['50.88916732998306', '-0.5768395884825535'],
    ],
];

$config = require __DIR__ . '/.config.php';
$geocode = new Geocode($config['apiKey']);

foreach ($methodArgLists as $methodName => $argLists) {
    $i = 0;

    foreach ($argLists as $argList) {
        $geoCoordinates = $geocode->{$methodName}(...$argList);

        $argListStr = implode(', ', array_map(function ($arg) {
            return is_string($arg)
                ? "'{$arg}'"
                : $arg
            ;
        }, $argList));

        printf(
            "\033[1;35m#%d\033[0m \033[1;32m%s(%s)\033[0m => %s\n",
            $i,
            $methodName,
            $argListStr,
            print_r($geoCoordinates, true)  // phpcs:ignore
        );

        $i++;
    }
}
