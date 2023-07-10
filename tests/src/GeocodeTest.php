<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi\Tests;

use InvalidArgumentException;
use PowderBlue\GeocodingApi\Geocode;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use const null;

/**
 * @phpstan-import-type GeocodeParameters from Geocode
 */
class GeocodeTest extends TestCase
{
    public function testIsInstantiable(): void
    {
        $apiKey = 'something';
        $geocode = new Geocode($apiKey);

        $this->assertSame($apiKey, $geocode->getApiKey());
    }

    /** @return array<mixed[]> */
    public function providesGeocodingUrls(): array
    {
        return [
            [
                'https://maps.googleapis.com/maps/api/geocode/json?key=foo&address=25%20Old%20Gardens%20Close%20Tunbridge%20Wells%20TN2%205ND&language=en-GB',
                [
                    [
                        'address' => '25 Old Gardens Close Tunbridge Wells TN2 5ND',
                    ],
                ],
                'foo',
            ],
            [
                'https://maps.googleapis.com/maps/api/geocode/json?key=bar&address=25%20Old%20Gardens%20Close%20Tunbridge%20Wells%20TN2%205ND&region=uk&language=en-GB',
                [
                    [
                        'address' => '25 Old Gardens Close Tunbridge Wells TN2 5ND',
                        'region' => 'uk',
                    ],
                ],
                'bar',
            ],
            [  // #2
                'https://maps.googleapis.com/maps/api/geocode/json?key=baz&address=25%20Old%20Gardens%20Close%20Tunbridge%20Wells%20TN2%205ND&language=en-GB',
                [
                    [
                        'address' => '25 Old Gardens Close Tunbridge Wells TN2 5ND',
                        'region' => null,  // `null`s are removed
                    ],
                ],
                'baz',
            ],
            [
                'https://maps.googleapis.com/maps/api/geocode/json?key=qux&latlng=40.714224%2C-73.961452&language=en-GB&region=us',
                [
                    [
                        'latlng' => '40.714224,-73.961452',
                        'language' => 'en-GB',
                        'region' => 'us',
                    ],
                ],
                'qux',
            ],
            [
                'https://maps.googleapis.com/maps/api/geocode/json?key=quux&address=25%20Old%20Gardens%20Close%20Tunbridge%20Wells%20TN2%205ND&language=en-GB&region=fr',
                [
                    [
                        'address' => '25 Old Gardens Close Tunbridge Wells TN2 5ND',
                    ],
                    'fr',
                ],
                'quux',
            ],
            [  // #5
                'https://maps.googleapis.com/maps/api/geocode/json?key=corge&address=25%20Old%20Gardens%20Close%20Tunbridge%20Wells%20TN2%205ND&language=en-GB',
                [
                    [
                        'address' => '25 Old Gardens Close Tunbridge Wells TN2 5ND',
                        'region' => 'fr',
                    ],
                    null,  // Explicitly "use no region"
                ],
                'corge',
            ],
            [
                'https://maps.googleapis.com/maps/api/geocode/json?key=grault&address=25%20Old%20Gardens%20Close%20Tunbridge%20Wells%20TN2%205ND&region=uk&language=en-GB',
                [
                    [
                        'address' => '25 Old Gardens Close Tunbridge Wells TN2 5ND',
                        'region' => 'fr',
                    ],
                    'uk',  // Overrides the region in the parameters
                ],
                'grault',
            ],
            // [
            //     'https://maps.googleapis.com/maps/api/geocode/json?key=garply&address=25%20Old%20Gardens%20Close%20Tunbridge%20Wells%20TN2%205ND&language=fr',
            //     [
            //         [
            //             'address' => '25 Old Gardens Close Tunbridge Wells TN2 5ND',
            //         ],
            //         null,
            //         'fr',
            //     ],
            //     'garply',
            // ],
            // [  // #8
            //     'https://maps.googleapis.com/maps/api/geocode/json?key=waldo&address=25%20Old%20Gardens%20Close%20Tunbridge%20Wells%20TN2%205ND&language=en',
            //     [
            //         [
            //             'address' => '25 Old Gardens Close Tunbridge Wells TN2 5ND',
            //             'language' => 'fr'
            //         ],
            //         null,
            //         'en',  // Overrides the language in the parameters
            //     ],
            //     'waldo',
            // ],
        ];
    }

    /**
     * @dataProvider providesGeocodingUrls
     * @param array{0:GeocodeParameters,1?:string|null} $argsForCreateUrl
     */
    public function testCreateurlCreatesTheUrlOfAGeocodingRequest(
        string $expectedUrl,
        array $argsForCreateUrl,
        string $apiKey
    ): void {
        $geocode = new Geocode($apiKey);

        $this->assertSame($expectedUrl, $geocode->createUrl(...$argsForCreateUrl));
    }

    public function testIsInvokable(): void
    {
        $invokeMethodName = '__invoke';

        $class = new ReflectionClass(Geocode::class);

        $this->assertTrue($class->hasMethod($invokeMethodName));

        $invokeMethod = $class->getMethod($invokeMethodName);

        $this->assertTrue($invokeMethod->isPublic());
    }

    /** @return array<mixed[]> */
    public function providesInvalidCountryCodes(): array
    {
        return [
            [
                InvalidArgumentException::class,
                'The format of the country code (`United Kingdom`) is invalid',
                'United Kingdom',
            ],
            [
                InvalidArgumentException::class,
                'Country code `yz` does not exist',
                'yz',
            ],
        ];
    }

    /**
     * @dataProvider providesInvalidCountryCodes
     * @phpstan-param class-string<\Exception> $expectedException
     */
    public function testByaddressThrowsAnExceptionIfTheCountryCodeIsInvalid(
        string $expectedException,
        string $expectedMessage,
        string $invalidCountryCode
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        $geocode = new Geocode('ignored');
        $geocode->byAddress('ignored', $invalidCountryCode);
    }
}
