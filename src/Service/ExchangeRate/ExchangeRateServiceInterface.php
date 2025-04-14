<?php

declare(strict_types=1);

namespace App\Service\ExchangeRate;

use App\Exception\ExchangeRateException;

interface ExchangeRateServiceInterface
{
    /**
     * Get exchange rate for a currency
     *
     * @param string $currency Currency code (e.g., 'USD')
     * @return float|null Exchange rate relative to EUR
     * @throws ExchangeRateException
     */
    public function getRate(string $currency): ?float;
}
