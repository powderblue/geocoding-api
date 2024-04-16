<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi\Exception;

use RuntimeException;
use Throwable;

use function curl_errno;
use function curl_error;

use const null;

/**
 * For when something goes wrong after initializing a cURL session (i.e. when you have a cURL handle)
 */
class CurlException extends RuntimeException
{
    /**
     * @param \CurlHandle $curlHandle
     * @phpstan-param resource $curlHandle
     * @param Throwable|null $previous
     */
    public function __construct(
        string $functionName,
        $curlHandle,
        ?Throwable $previous = null
    ) {
        $message = "`{$functionName}()` failed: " . curl_error($curlHandle);

        parent::__construct(
            $message,
            curl_errno($curlHandle),
            $previous
        );
    }
}
