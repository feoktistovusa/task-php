<?php

declare(strict_types=1);

namespace App\Service\CountryValidator;

class EuCountryValidator implements EuCountryValidatorInterface
{
    /**
     * List of EU country codes (ISO 3166-1 alpha-2)
     */
    private const EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI',
        'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT',
        'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'
    ];

    /**
     * @inheritDoc
     */
    public function isEuCountry(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), self::EU_COUNTRIES, true);
    }
}
