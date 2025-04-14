<?php

declare(strict_types=1);

namespace Service\ExchangeRate;

use Exception\ExchangeRateException;

class ExchangeRatesApiService implements ExchangeRateServiceInterface
{
    private const API_URL = 'https://api.exchangeratesapi.io/latest';

    /**
     * @inheritDoc
     */
    public function getRate(string $currency): ?float
    {
        $response = $this->makeApiRequest();

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ExchangeRateException("Invalid exchange rate data: " . json_last_error_msg());
        }

        if (!isset($data['rates'][$currency])) {
            throw new ExchangeRateException("Exchange rate not found for currency: {$currency}");
        }

        $rate = $data['rates'][$currency];
        return ($rate > 0) ? $rate : null;
    }

    /**
     * Make API request to exchange rate service
     *
     * @return string
     * @throws ExchangeRateException
     */
    private function makeApiRequest(): string
    {
        $response = @file_get_contents(self::API_URL);

        if ($response === false) {
            throw new ExchangeRateException("Failed to connect to exchange rate service");
        }

        return $response;
    }
}
