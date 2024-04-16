<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi\Tests\Exception;

use PHPUnit\Framework\TestCase;
use PowderBlue\GeocodingApi\Exception\CurlException;
use RuntimeException;

use function curl_exec;
use function curl_init;
use function is_subclass_of;

class CurlExceptionTest extends TestCase
{
    public function testIsARuntimeException(): void
    {
        $this->assertTrue(is_subclass_of(CurlException::class, RuntimeException::class));
    }

    public function testBuildsAMessage(): void
    {
        /**
         * @var \CurlHandle
         * @phpstan-var resource
         */
        $curlHandle = curl_init('htt://example.com/');
        curl_exec($curlHandle);
        $ex = new CurlException('curl_exec', $curlHandle);

        $this->assertSame('`curl_exec()` failed: Protocol "htt" not supported or disabled in libcurl', $ex->getMessage());
    }
}
