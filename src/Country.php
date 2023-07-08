<?php

declare(strict_types=1);

namespace PowderBlue\GeocodingApi;

use InvalidArgumentException;
use Locale;

use function ctype_alpha;
use function strlen;
use function strtolower;
use function strtoupper;

class Country
{
    /** @var array<string,string> */
    private const ISO_ALPHA_2_TO_TLD_EXCEPTIONS = [
        'GB' => 'uk',
    ];

    /**
     * ISO 639-1 alpha-2 code
     */
    private string $defaultLang;

    /**
     * ISO 3166-1 alpha-2 code
     */
    private string $isoAlpha2;

    private string $longName;

    public function __construct(string $isoAlpha2)
    {
        $this
            ->setDefaultLang('en')
            ->setIsoAlpha2($isoAlpha2)
        ;
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

    private function setLongName(string $name): self
    {
        $this->longName = $name;

        return $this;
    }

    public function getLongName(): string
    {
        return $this->longName;
    }

    /**
     * @param string $code ISO 3166-1 alpha-2 code
     * @throws InvalidArgumentException If the format of the country code is invalid
     * @throws InvalidArgumentException If the country code does not exist
     */
    private function setIsoAlpha2(string $code): self
    {
        $codeInCorrectFormat = 2 === strlen($code) && ctype_alpha($code);

        if (!$codeInCorrectFormat) {
            throw new InvalidArgumentException("The format of the country code (`{$code}`) is invalid");
        }

        $normalizedCode = strtoupper($code);
        $longName = Locale::getDisplayRegion("-{$code}", $this->getDefaultLang());

        if ($normalizedCode === $longName) {
            throw new InvalidArgumentException("Country code `{$code}` does not exist");
        }

        $this->isoAlpha2 = $normalizedCode;

        return $this->setLongName($longName);
    }

    public function getIsoAlpha2(): string
    {
        return $this->isoAlpha2;
    }

    public function getTopLevelDomain(): string
    {
        $isoAlpha2 = $this->getIsoAlpha2();

        return strtolower(self::ISO_ALPHA_2_TO_TLD_EXCEPTIONS[$isoAlpha2] ?? $isoAlpha2);
    }
}
