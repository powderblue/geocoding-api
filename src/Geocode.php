<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi;

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
     * Undocumented function
     *
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
    private function invoke(array $parameters): GeocodingResponse
    {
        $forwardUrl = $this->createForwardUrl($parameters);
        $curlResponse = (new Curl())->get($forwardUrl);
        /** @var stdClass */
        $rawResponseData = $curlResponse->json();

        return new GeocodingResponse($rawResponseData);
    }

    /**
     * @phpstan-return SorgGeoCoordinates
     * @throws RuntimeException If geocoding was unsuccessful
     */
    public function byAddress(
        string $address,
        string $region = null
    ): ?array {
        $geocodingResponse = $this->invoke([
            'address' => $address,
            'region' => $region,
        ]);

        if (!$geocodingResponse->wasSuccessful()) {
            /** @var string */
            $errorInfo = $geocodingResponse->getErrorInfo();

            throw new RuntimeException($errorInfo);
        }

        return $geocodingResponse->getFirstGeoCoordinates();
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
