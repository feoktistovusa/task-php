<?php

declare(strict_types=1);

namespace Service\CountryValidator;

interface EuCountryValidatorInterface
{
    /**
     * Check if a country is in the EU
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @return bool
     */
    public function isEuCountry(string $countryCode): bool;
}
