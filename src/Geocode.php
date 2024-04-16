<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi;

use InvalidArgumentException;
use PowderBlue\GeocodingApi\Exception\CurlException;
use PowderBlue\GeocodingApi\Exception\CurlInitFailedException;
use RuntimeException;
use stdClass;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_replace;
use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function func_num_args;
use function http_build_query;
use function implode;
use function ini_get;
use function json_decode;

use const CURLINFO_RESPONSE_CODE;
use const CURLOPT_HTTPGET;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use const CURLOPT_USERAGENT;
use const false;
use const null;
use const PHP_QUERY_RFC3986;
use const true;

/**
 * Action
 *
 * @phpstan-import-type SorgGeoCoordinates from GeocodingResponse
 * @phpstan-type GeocodeParameters array<string,string|null>
 */
class Geocode
{
    /**
     * @var string
     */
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
     * @return \CurlHandle
     * @phpstan-return resource
     * @throws CurlInitFailedException If it failed to initialize a cURL session
     * @throws CurlException If it failed to set all cURL options
     */
    private function createCurlHandle(string $apiUrl)
    {
        /**
         * @var \CurlHandle|false
         * @phpstan-var resource|false
         */
        $curlHandle = curl_init();

        if (false === $curlHandle) {
            throw new CurlInitFailedException();
        }

        $phpName = 'PHP ' . PHP_VERSION;
        $userAgentStr = "com.powder-blue.geocoding-api/* ({$phpName}; ext-curl; https://github.com/powderblue/geocoding-api)";

        $allOptionsSet = curl_setopt_array($curlHandle, [
            CURLOPT_HTTPGET => true,
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => $userAgentStr,
        ]);

        if (!$allOptionsSet) {
            throw new CurlException('curl_setopt_array', $curlHandle);
        }

        return $curlHandle;
    }

    /**
     * @phpstan-param GeocodeParameters $parameters
     * @param Country|string|null $regionOrCountryBias `null` means "don't apply a region bias"
     * @throws CurlException If it failed to perform the cURL session
     * @throws CurlException If it failed to get information about the request from the cURL handle
     * @throws RuntimeException If the HTTP request was unsuccessful
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

        $curlHandle = null;

        try {
            $curlHandle = $this->createCurlHandle($apiUrl);
            /** @var string|false */
            $responseBody = curl_exec($curlHandle);

            if (false === $responseBody) {
                throw new CurlException('curl_exec', $curlHandle);
            }

            /** @var int|false */
            $responseCode = curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);

            if (false === $responseCode) {
                throw new CurlException('curl_getinfo', $curlHandle);
            }
        } finally {
            if ($curlHandle) {
                // @todo Remove this after upgrading to PHP8
                curl_close($curlHandle);
            }
        }

        $httpRequestSuccessful = $responseCode >= 200 && $responseCode <= 299;

        if (!$httpRequestSuccessful) {
            throw new RuntimeException('The HTTP request was unsuccessful', $responseCode);
        }

        /** @var stdClass */
        $apiData = json_decode($responseBody);

        return new GeocodingResponse($apiData);
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
