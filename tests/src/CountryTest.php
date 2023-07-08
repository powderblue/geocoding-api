<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PowderBlue\GeocodingApi\Country;

class CountryTest extends TestCase
{
    public function testIsInstantiable(): void
    {
        $country = new Country('GB');

        $this->assertSame('GB', $country->getIsoAlpha2());
    }

    public function testTheCountryCodeIsNormalized(): void
    {
        $country = new Country('gb');

        $this->assertSame('GB', $country->getIsoAlpha2());
    }

    public function testThrowsAnExceptionIfTheFormatOfTheCountryCodeIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The format of the country code (`United Kingdom`) is invalid');

        new Country('United Kingdom');
    }

    public function testThrowsAnExceptionIfTheCountryCodeDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Country code `yz` does not exist');

        new Country('yz');
    }

    public function testGetlongnameReturnsTheLongNameOfTheCountry(): void
    {
        $country = new Country('gb');

        $this->assertSame('United Kingdom', $country->getLongName());
    }

    /** @return array<mixed[]> */
    public function providesCountryCodeTopLevelDomains(): array
    {
        return [
            // ccTLD => ISO 3166-1 alpha-2 code
            ['fr', 'fr'],
            ['uk', 'gb'],
            ['it', 'it'],
            ['mc', 'mc'],
            ['es', 'es'],
            ['ch', 'ch'],
            ['fr', 'FR'],
            ['uk', 'GB'],
            ['fr', 'Fr'],
            ['uk', 'gB'],
        ];
    }

    /** @dataProvider providesCountryCodeTopLevelDomains */
    public function testGettopleveldomainReturnsTheTopLevelDomainForTheCountry(
        ?string $expectedTld,
        string $isoAlpha2
    ): void {
        $country = new Country($isoAlpha2);

        $this->assertSame($expectedTld, $country->getTopLevelDomain());
    }
}
