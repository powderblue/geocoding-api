<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi;

use InvalidArgumentException;
use PowderBlue\Curl\Curl;
use RuntimeException;
use stdClass;

use function array_filter;
use function array_replace;
use function http_build_query;
use function ini_get;

use const null;
use const PHP_QUERY_RFC3986;

/**
 * Action
 *
 * @phpstan-import-type SorgGeoCoordinates from GeocodingResponse
 * @phpstan-type GeocodeParameters array<string,string|null>
 */
class Geocode
{
    /** @var string */
    private const SERVICE_BASE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->setApiKey($apiKey);
    }

    /**
     * @phpstan-param GeocodeParameters $parameters
     * @return string
     */
    public function createForwardUrl(array $parameters): string
    {
        $notNullParameters = array_filter(array_replace([
            'key' => $this->getApiKey(),
        ], $parameters), function ($value): bool {
            return null !== $value;
        });

        /** @var string */
        $argSeparator = ini_get('arg_separator.output');
        $queryStr = http_build_query($notNullParameters, '', $argSeparator, PHP_QUERY_RFC3986);

        return self::SERVICE_BASE_URL . "?{$queryStr}";
    }

    /**
     * @phpstan-param GeocodeParameters $parameters
     */
    public function __invoke(array $parameters): GeocodingResponse
    {
        $forwardUrl = $this->createForwardUrl($parameters);
        $curlResponse = (new Curl())->get($forwardUrl);
        /** @var stdClass */
        $rawResponseData = $curlResponse->json();

        return new GeocodingResponse($rawResponseData);
    }

    /**
     * Convenience method
     *
     * @phpstan-return SorgGeoCoordinates
     * @throws InvalidArgumentException If the format of the country code is invalid
     * @throws InvalidArgumentException If the country code does not exist
     * @throws RuntimeException If geocoding was unsuccessful
     */
    public function byAddress(
        string $address,
        string $countryIsoAlpha2 = null
    ): array {
        $tld = null;

        if (null !== $countryIsoAlpha2) {
            $country = new Country($countryIsoAlpha2);
            $tld = $country->getTopLevelDomain();
        }

        $geocodingResponse = $this([
            'address' => $address,
            'region' => $tld,
        ]);

        if (!$geocodingResponse->wasSuccessful()) {
            /** @var string */
            $errorInfo = $geocodingResponse->getErrorInfo();

            throw new RuntimeException($errorInfo);
        }

        /** @phpstan-var SorgGeoCoordinates */
        return $geocodingResponse->getFirstGeoCoordinates();
    }

    /**
     * Convenience method
     *
     * (A postcode must be accompanied by a country, to give it context)
     *
     * @phpstan-return SorgGeoCoordinates
     * @throws InvalidArgumentException If the format of the country code is invalid
     * @throws InvalidArgumentException If the country code does not exist
     * @throws RuntimeException If geocoding was unsuccessful
     */
    public function byPostcode(
        string $postcode,
        string $countryIsoAlpha2
    ): array {
        $country = new Country($countryIsoAlpha2);

        // Including the full country-name in the address guarantees to reduce ambiguity
        return $this->byAddress("{$postcode} {$country->getLongName()}", $country->getIsoAlpha2());
    }

    private function setApiKey(string $key): self
    {
        $this->apiKey = $key;

        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }
}
