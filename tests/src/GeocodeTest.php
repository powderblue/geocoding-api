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
    public function providesForwardUrls(): array
    {
        return [
            [
                'https://maps.googleapis.com/maps/api/geocode/json?key=foo&address=25%20Old%20Gardens%20Close%20Tunbridge%20Wells%20TN2%205ND',
                [
                    'address' => '25 Old Gardens Close Tunbridge Wells TN2 5ND',
                ],
                'foo',
            ],
            [
                'https://maps.googleapis.com/maps/api/geocode/json?key=bar&address=25%20Old%20Gardens%20Close%20Tunbridge%20Wells%20TN2%205ND&region=uk',
                [
                    'address' => '25 Old Gardens Close Tunbridge Wells TN2 5ND',
                    'region' => 'uk',
                ],
                'bar',
            ],
            [
                'https://maps.googleapis.com/maps/api/geocode/json?key=foo&address=25%20Old%20Gardens%20Close%20Tunbridge%20Wells%20TN2%205ND',
                [
                    'address' => '25 Old Gardens Close Tunbridge Wells TN2 5ND',
                    'region' => null,
                ],
                'foo',
            ],
        ];
    }

    /**
     * @dataProvider providesForwardUrls
     * @phpstan-param GeocodeParameters $geocodeParameters
     */
    public function testCreateforwardurlCreatesTheUrlOfAForwardGeocodingRequest(
        string $expectedUrl,
        array $geocodeParameters,
        string $apiKey
    ): void {
        $geocode = new Geocode($apiKey);

        $this->assertSame($expectedUrl, $geocode->createForwardUrl($geocodeParameters));
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
