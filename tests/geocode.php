#!/usr/bin/env php
<?php

use PowderBlue\GeocodingApi\Geocode;

require dirname(__DIR__) . '/vendor/autoload.php';

$byAddressArgLists = [
    ['25 Old Gardens Close Tunbridge Wells TN2 5ND', 'uk'],
    ['Les Houches', 'fr'],
    ['Chamonix - Les Houches', 'fr'],
    ['Chamonix - Centre', 'fr'],
];

$config = require __DIR__ . '/.config.php';
$geocode = new Geocode($config['apiKey']);

foreach ($byAddressArgLists as $argList) {
    $geoCoordinates = $geocode->byAddress(...$argList);

    $argListStr = implode(', ', array_map(function (string $arg): string {
        return "'{$arg}'";
    }, $argList));

    // phpcs:ignore
    echo sprintf("(%s) => %s\n", $argListStr, print_r($geoCoordinates, true));
}
