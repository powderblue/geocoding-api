<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi\Tests\Exception;

use PHPUnit\Framework\TestCase;
use PowderBlue\GeocodingApi\Exception\CurlInitFailedException;
use RuntimeException;

use function is_subclass_of;

class CurlInitFailedExceptionTest extends TestCase
{
    public function testIsARuntimeException(): void
    {
        $this->assertTrue(is_subclass_of(CurlInitFailedException::class, RuntimeException::class));
    }
}
