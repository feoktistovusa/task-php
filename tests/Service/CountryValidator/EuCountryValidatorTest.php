<?php

declare(strict_types=1);

namespace App\Tests\Service\CountryValidator;

use App\Service\CountryValidator\EuCountryValidator;
use PHPUnit\Framework\TestCase;

class EuCountryValidatorTest extends TestCase
{
    public function testIsEuCountry(): void
    {
        $validator = new EuCountryValidator();

        // Test EU countries
        $this->assertTrue($validator->isEuCountry('DE')); // Germany
        $this->assertTrue($validator->isEuCountry('fr')); // France (lowercase)
        $this->assertTrue($validator->isEuCountry('IT')); // Italy

        // Test non-EU countries
        $this->assertFalse($validator->isEuCountry('US')); // United States
        $this->assertFalse($validator->isEuCountry('GB')); // United Kingdom (post-Brexit)
        $this->assertFalse($validator->isEuCountry('CH')); // Switzerland
        $this->assertFalse($validator->isEuCountry('XX')); // Invalid code
    }
}
