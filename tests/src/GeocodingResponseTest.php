<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi\Tests;

use PowderBlue\GeocodingApi\GeocodingResponse;
use PHPUnit\Framework\TestCase;
use stdClass;

use const false;
use const null;
use const true;

/**
 * @phpstan-import-type SorgGeoCoordinates from GeocodingResponse
 */
class GeocodingResponseTest extends TestCase
{
    /** @factory GeocodingResponse */
    private function createMinimalGeocodingResponse(string $status): GeocodingResponse
    {
        $rawData = new stdClass();
        $rawData->results = [];
        $rawData->status = $status;

        return new GeocodingResponse($rawData);
    }

    public function testIsInstantiable(): void
    {
        $rawData = new stdClass();
        $rawData->status = GeocodingResponse::STATUS_OK;

        $response = new GeocodingResponse($rawData);

        $this->assertSame($rawData, $response->getRawData());
    }

    /** @return array<mixed[]> */
    public function providesSuccessfulResponses(): array
    {
        return [
            [
                true,
                $this->createMinimalGeocodingResponse(GeocodingResponse::STATUS_OK),
            ],
            [
                false,
                $this->createMinimalGeocodingResponse('SOMETHING_ELSE'),
            ],
        ];
    }

    /** @dataProvider providesSuccessfulResponses */
    public function testWassuccessfulReturnsTrueIfGeocodingWasSuccessful(
        bool $expected,
        GeocodingResponse $geocodingResponse
    ): void {
        $this->assertSame($expected, $geocodingResponse->wasSuccessful());
    }

    /** @return array<mixed[]> */
    public function providesSorggeocoordinates(): array
    {
        return [
            [
                [
                    'address' => [
                        'streetAddress' => '25 Old Gardens Close',
                        'addressLocality' => 'Tunbridge Wells',
                        'addressRegion' => 'Kent',
                        'postalCode' => 'TN2 5ND',
                        'addressCountry' => 'GB',
                    ],
                    'latitude' => 51.1172303,
                    'longitude' => 0.2635245,
                ],
                (object) array(
                    'results' =>
                   array (
                     0 =>
                     (object) array(
                        'address_components' =>
                       array (
                         0 =>
                         (object) array(
                            'long_name' => '25',
                            'short_name' => '25',
                            'types' =>
                           array (
                             0 => 'street_number',
                           ),
                         ),
                         1 =>
                         (object) array(
                            'long_name' => 'Old Gardens Close',
                            'short_name' => 'Old Gardens Cl',
                            'types' =>
                           array (
                             0 => 'route',
                           ),
                         ),
                         2 =>
                         (object) array(
                            'long_name' => 'Royal Tunbridge Wells',
                            'short_name' => 'Royal Tunbridge Wells',
                            'types' =>
                           array (
                             0 => 'locality',
                             1 => 'political',
                           ),
                         ),
                         3 =>
                         (object) array(
                            'long_name' => 'Tunbridge Wells',
                            'short_name' => 'Tunbridge Wells',
                            'types' =>
                           array (
                             0 => 'postal_town',
                           ),
                         ),
                         4 =>
                         (object) array(
                            'long_name' => 'Kent',
                            'short_name' => 'Kent',
                            'types' =>
                           array (
                             0 => 'administrative_area_level_2',
                             1 => 'political',
                           ),
                         ),
                         5 =>
                         (object) array(
                            'long_name' => 'England',
                            'short_name' => 'England',
                            'types' =>
                           array (
                             0 => 'administrative_area_level_1',
                             1 => 'political',
                           ),
                         ),
                         6 =>
                         (object) array(
                            'long_name' => 'United Kingdom',
                            'short_name' => 'GB',
                            'types' =>
                           array (
                             0 => 'country',
                             1 => 'political',
                           ),
                         ),
                         7 =>
                         (object) array(
                            'long_name' => 'TN2 5ND',
                            'short_name' => 'TN2 5ND',
                            'types' =>
                           array (
                             0 => 'postal_code',
                           ),
                         ),
                       ),
                        'formatted_address' => '25 Old Gardens Cl, Royal Tunbridge Wells, Tunbridge Wells TN2 5ND, UK',
                        'geometry' =>
                       (object) array(
                          'bounds' =>
                         (object) array(
                            'northeast' =>
                           (object) array(
                              'lat' => 51.117299,
                              'lng' => 0.2636517,
                           ),
                            'southwest' =>
                           (object) array(
                              'lat' => 51.117181,
                              'lng' => 0.2634342,
                           ),
                         ),
                          'location' =>
                         (object) array(
                            'lat' => 51.1172303,
                            'lng' => 0.2635245,
                         ),
                          'location_type' => 'ROOFTOP',
                          'viewport' =>
                         (object) array(
                            'northeast' =>
                           (object) array(
                              'lat' => 51.1186104802915,
                              'lng' => 0.2648949302915021,
                           ),
                            'southwest' =>
                           (object) array(
                              'lat' => 51.1159125197085,
                              'lng' => 0.262196969708498,
                           ),
                         ),
                       ),
                        'place_id' => 'ChIJlbSOnD1E30cRDGnQ34LElA4',
                        'types' =>
                       array (
                         0 => 'premise',
                       ),
                     ),
                   ),
                    'status' => 'OK',
                ),
            ],
            [
                null,
                (object) [
                    'results' => [],
                    'status' => 'UNKNOWN_ERROR',
                    'error_message' => 'Something went wrong',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providesSorggeocoordinates
     * @phpstan-param SorgGeoCoordinates|null $expected
     */
    public function testGetfirstgeocoordinates(
        ?array $expected,
        stdClass $rawData
    ): void {
        $response = new GeocodingResponse($rawData);

        $this->assertSame($expected, $response->getFirstGeoCoordinates());
    }

    /** @return array<mixed[]> */
    public function providesResponsesToUnsuccessfulRequests(): array
    {
        $argLists = [];

        $rawData = new stdClass();
        $rawData->status = 'NOT_OK';

        $argLists[] = [
            'NOT_OK',
            $rawData,
        ];

        $rawData = new stdClass();
        $rawData->status = 'NOT_OK';
        $rawData->error_message = 'Something went wrong';

        $argLists[] = [
            'NOT_OK: Something went wrong',
            $rawData,
        ];

        $rawData = new stdClass();
        $rawData->status = GeocodingResponse::STATUS_OK;

        $argLists[] = [
            null,
            $rawData,
        ];

        return $argLists;
    }

    /** @dataProvider providesResponsesToUnsuccessfulRequests */
    public function testGeterrorinfoReturnsAMessageContainingDetailsOfTheErrorThatOccurred(
        ?string $expected,
        stdClass $rawResponseData
    ): void {
        $response = new GeocodingResponse($rawResponseData);

        $this->assertSame($expected, $response->getErrorInfo());
    }
}
