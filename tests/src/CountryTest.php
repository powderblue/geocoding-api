<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PowderBlue\GeocodingApi\Country;

class CountryTest extends TestCase
{
    /** @factory Country */
    private function createCountry(string $countryIsoAlpha2 = 'GB'): Country
    {
        return new Country($countryIsoAlpha2, 'en-GB');
    }

    public function testIsInstantiable(): void
    {
        $country = new Country('GB', 'en-GB');

        $this->assertSame('GB', $country->getIsoAlpha2());
        $this->assertSame('en-GB', $country->getDefaultLang());
    }

    public function testTheCountryCodeIsNormalized(): void
    {
        $country = new Country('gb', 'en-GB');

        $this->assertSame('GB', $country->getIsoAlpha2());
    }

    public function testFactoryMethodInThisTestCase(): void
    {
        $defaultReturnValue = $this->createCountry();

        $this->assertSame('GB', $defaultReturnValue->getIsoAlpha2());
        $this->assertSame('en-GB', $defaultReturnValue->getDefaultLang());

        $france = $this->createCountry('FR');

        $this->assertSame('FR', $france->getIsoAlpha2());
        $this->assertSame('en-GB', $france->getDefaultLang());
    }

    public function testThrowsAnExceptionIfTheFormatOfTheCountryCodeIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The format of the country code (`United Kingdom`) is invalid');

        $this->createCountry('United Kingdom');
    }

    public function testThrowsAnExceptionIfTheCountryCodeDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Country code `YZ` does not exist');

        $this->createCountry('YZ');
    }

    public function testGetlongnameReturnsTheLongNameOfTheCountry(): void
    {
        $country = $this->createCountry('GB');

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
        $country = $this->createCountry($isoAlpha2);

        $this->assertSame($expectedTld, $country->getTopLevelDomain());
    }
}
