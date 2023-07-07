<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi;

use stdClass;

use function array_filter;
use function implode;
use function in_array;
use function property_exists;
use function reset;

use const null;

/**
 * @phpstan-type SorgPostalAddress array{streetAddress:string,addressLocality:string,addressRegion:string,postalCode:string,addressCountry:string}
 * @phpstan-type SorgGeoCoordinates array{address:SorgPostalAddress,latitude:float,longitude:float}
 */
class GeocodingResponse
{
    /** @var string */
    public const STATUS_OK = 'OK';

    /** @var string[] */
    private const STREET_NUMBER_AFTER_ROUTE_COUNTRIES = ['CH', 'ES', 'IT'];

    private stdClass $rawData;

    public function __construct(stdClass $rawData)
    {
        $this->setRawData($rawData);
    }

    /**
     * @return stdClass|null
     */
    private function getFirstResult()
    {
        return reset($this->getRawData()->results)
            ?: null
        ;
    }

    private function getFirstMatchingAddressComponentFromResult(
        stdClass $result,
        string $type
    ): ?stdClass {
        foreach ($result->address_components as $addressComponent) {
            if (in_array($type, $addressComponent->types)) {
                return $addressComponent;
            }
        }

        return null;
    }

    /**
     * This should return `null` only if the geocoding request was unsuccessful
     *
     * @phpstan-return SorgGeoCoordinates|null
     */
    public function getFirstGeoCoordinates(): ?array
    {
        $firstResult = $this->getFirstResult();

        if (!$firstResult) {
            return null;
        }

        $country = $this->getFirstMatchingAddressComponentFromResult($firstResult, 'country');
        $addressCountry = $country ? $country->short_name : null;

        $route = $this->getFirstMatchingAddressComponentFromResult($firstResult, 'route');

        $streetAddress = $route ? $route->long_name : null;

        if ($streetAddress) {
            $streetNumber = $this->getFirstMatchingAddressComponentFromResult($firstResult, 'street_number');

            if ($streetNumber) {
                if (in_array($addressCountry, self::STREET_NUMBER_AFTER_ROUTE_COUNTRIES)) {
                    $streetAddress .= " {$streetNumber->long_name}";
                } else {
                    $streetAddress = "{$streetNumber->long_name} {$streetAddress}";
                }
            }
        }

        $postalTown = $this->getFirstMatchingAddressComponentFromResult($firstResult, 'postal_town')
            ?: $this->getFirstMatchingAddressComponentFromResult($firstResult, 'locality')
        ;

        $adminAreaL2 = $this->getFirstMatchingAddressComponentFromResult($firstResult, 'administrative_area_level_2');
        $postalCode = $this->getFirstMatchingAddressComponentFromResult($firstResult, 'postal_code');

        $location = $firstResult->geometry->location;

        return [
            'address' => [
                'streetAddress' => $streetAddress,
                'addressLocality' => $postalTown ? $postalTown->long_name : null,
                'addressRegion' => $adminAreaL2 ? $adminAreaL2->long_name : null,
                'postalCode' => $postalCode ? $postalCode->long_name : null,
                'addressCountry' => $addressCountry,
            ],
            'latitude' => $location->lat,
            'longitude' => $location->lng,
        ];
    }

    private function setRawData(stdClass $data): self
    {
        $this->rawData = $data;

        return $this;
    }

    public function getRawData(): stdClass
    {
        return $this->rawData;
    }

    private function getStatus(): string
    {
        return $this->getRawData()->status;
    }

    public function wasSuccessful(): bool
    {
        return self::STATUS_OK === $this->getStatus();
    }

    private function getErrorMessage(): ?string
    {
        return property_exists($this->getRawData(), 'error_message')
            ? $this->getRawData()->error_message
            : null
        ;
    }

    public function getErrorInfo(): ?string
    {
        if ($this->wasSuccessful()) {
            return null;
        }

        return implode(': ', array_filter([
            $this->getStatus(),
            $this->getErrorMessage(),
        ]));
    }
}
