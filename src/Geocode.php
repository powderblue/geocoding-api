<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi;

use InvalidArgumentException;
use PowderBlue\Curl\Curl;
use RuntimeException;
use stdClass;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_replace;
use function func_num_args;
use function http_build_query;
use function implode;
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
    private const API_BASE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    /**
     * ISO 639-1 code
     */
    private string $defaultLang;

    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this
            ->setDefaultLang('en-GB')
            ->setApiKey($apiKey)
        ;
    }

    /**
     * @phpstan-param GeocodeParameters $parameters
     * @param string|null $region `null` means "don't apply a region bias"
     * @return string
     */
    public function createUrl(
        array $parameters,
        ?string $region = null
    ): string {
        $overrideParams = [];

        // // Language explicitly set
        // if (func_num_args() > 2) {
        //     // Use the specified language unless it's `null`, which means "use the default language" -- because we have
        //     // to use *a* language
        //     $overrideParams['language'] = null === $language
        //         ? $this->getDefaultLang()
        //         : $language
        //     ;
        // } else {
        //     // Use the language specified in the parameters unless there isn't one
        //     if (!array_key_exists('language', $parameters)) {
        //         $overrideParams['language'] = $this->getDefaultLang();
        //     }
        // }

        // Use the language specified in the parameters unless there isn't one
        if (!array_key_exists('language', $parameters)) {
            $overrideParams['language'] = $this->getDefaultLang();
        }

        // Use the region only if explicitly set
        if (func_num_args() > 1) {
            $overrideParams['region'] = $region;
        }

        $augmentedParams = array_replace([
            'key' => $this->getApiKey(),
        ], $parameters, $overrideParams);

        $notNullAugmentedParams = array_filter($augmentedParams, function ($value): bool {
            return null !== $value;
        });

        /** @var string */
        $argSeparator = ini_get('arg_separator.output');
        // 'All reserved characters (for example the plus sign "+") must be URL-encoded.' and "Street address elements
        // should be delimited by spaces (shown here as url-escaped to %20)"
        $queryStr = http_build_query($notNullAugmentedParams, '', $argSeparator, PHP_QUERY_RFC3986);

        return self::API_BASE_URL . "?{$queryStr}";
    }

    /**
     * @phpstan-param GeocodeParameters $parameters
     * @param Country|string|null $regionOrCountryBias `null` means "don't apply a region bias"
     */
    public function __invoke(
        array $parameters,
        $regionOrCountryBias = null
    ): GeocodingResponse {
        $region = $regionOrCountryBias instanceof Country
            ? $regionOrCountryBias->getTopLevelDomain()
            : $regionOrCountryBias
        ;

        $apiUrl = $this->createUrl($parameters, $region);
        $curlResponse = (new Curl())->get($apiUrl);
        /** @var stdClass */
        $rawResponseData = $curlResponse->json();

        return new GeocodingResponse($rawResponseData);
    }

    /**
     * `null` is permissible: it means "no country"
     *
     * @factory Country
     * @throws InvalidArgumentException If the format of the country code is invalid
     * @throws InvalidArgumentException If the country code does not exist
     */
    private function createDefaultCountryOrNull(?string $countryIsoAlpha2): ?Country
    {
        return null === $countryIsoAlpha2
            ? null
            : new Country($countryIsoAlpha2, $this->getDefaultLang())
        ;
    }

    /**
     * Top-level convenience method
     *
     * @phpstan-param GeocodeParameters $parameters
     * @param string|null $countryIsoAlpha2Bias `null` means "don't apply a region bias"
     * @phpstan-return SorgGeoCoordinates
     * @throws RuntimeException If geocoding was unsuccessful
     * @todo Rename this
     */
    private function byParameters(
        array $parameters,
        ?string $countryIsoAlpha2Bias
    ): array {
        $geocodingResponse = $this(
            $parameters,
            $this->createDefaultCountryOrNull($countryIsoAlpha2Bias)
        );

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
     * N.B. Street address elements should be delimited by spaces
     *
     * @phpstan-return SorgGeoCoordinates
     * @throws InvalidArgumentException If the format of the country code is invalid
     * @throws InvalidArgumentException If the country code does not exist
     * @throws RuntimeException If geocoding was unsuccessful
     */
    public function byAddress(
        string $address,
        string $countryIsoAlpha2Bias = null
    ): array {
        return $this->byParameters([
            'address' => $address,
        ], $countryIsoAlpha2Bias);
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
        $country = $this->createDefaultCountryOrNull($countryIsoAlpha2);
        /** @var Country $country */

        // Including the full country-name in the address guarantees to reduce ambiguity
        return $this->byAddress(
            "{$postcode} {$country->getLongName()}",
            $country->getIsoAlpha2()  // Kinda self-testing
        );
    }

    /**
     * Convenience method
     *
     * @param string|float $lat
     * @param string|float $long
     * @phpstan-return SorgGeoCoordinates
     * @throws InvalidArgumentException If the format of the country code is invalid
     * @throws InvalidArgumentException If the country code does not exist
     * @throws RuntimeException If geocoding was unsuccessful
     */
    public function byLatLong(
        $lat,
        $long,
        string $countryIsoAlpha2Bias = null
    ): array {
        // @todo Validate lat/long?

        // "Ensure that no space exists between the latitude and longitude values when passed in the latlng parameter"
        /** @phpstan-ignore-next-line */
        $trimmedParameters = array_map('\trim', [$lat, $long]);

        return $this->byParameters([
            'latlng' => implode(',', $trimmedParameters),
        ], $countryIsoAlpha2Bias);
    }

    private function setDefaultLang(string $code): self
    {
        $this->defaultLang = $code;

        return $this;
    }

    private function getDefaultLang(): string
    {
        return $this->defaultLang;
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
