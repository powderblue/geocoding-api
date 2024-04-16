<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi\Exception;

use RuntimeException;

/**
 * This isn't a "cURL exception" because we haven't gotten into cURL by this point
 */
class CurlInitFailedException extends RuntimeException
{
    /** @phpstan-ignore-next-line */
    protected $message = 'The `curl_init()` call failed';
}
