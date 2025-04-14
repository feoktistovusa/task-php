<?php

declare(strict_types=1);

namespace App\Processor;

use App\Exception\BinLookupException;
use App\Exception\ExchangeRateException;
use App\Exception\FileReadException;
use App\Exception\JsonParseException;
use App\Model\Transaction;
use App\Service\BinLookup\BinLookupServiceInterface;
use App\Service\CountryValidator\EuCountryValidatorInterface;
use App\Service\ExchangeRate\ExchangeRateServiceInterface;

class TransactionProcessor
{
    private const EU_COMMISSION_RATE = 0.01;
    private const NON_EU_COMMISSION_RATE = 0.02;
    private const BASE_CURRENCY = 'EUR';

    private BinLookupServiceInterface $binLookupService;
    private ExchangeRateServiceInterface $exchangeRateService;
    private EuCountryValidatorInterface $euCountryValidator;

    public function __construct(
        BinLookupServiceInterface $binLookupService,
        ExchangeRateServiceInterface $exchangeRateService,
        EuCountryValidatorInterface $euCountryValidator
    ) {
        $this->binLookupService = $binLookupService;
        $this->exchangeRateService = $exchangeRateService;
        $this->euCountryValidator = $euCountryValidator;
    }

    /**
     * Process a file containing transactions and return an array of commission amounts
     *
     * @param string $filePath Path to the file containing transactions
     * @return array Array of calculated commissions
     * @throws FileReadException If the file cannot be read
     * @throws JsonParseException If transaction data cannot be parsed
     * @throws BinLookupException If BIN lookup fails
     * @throws ExchangeRateException If exchange rate lookup fails
     */
    public function processFile(string $filePath): array
    {
        $fileContents = $this->readFile($filePath);
        $lines = explode("\n", $fileContents);
        $results = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $transaction = $this->parseTransaction($line);
            $results[] = $this->calculateCommission($transaction);
        }

        return $results;
    }

    /**
     * Calculate commission for a single transaction
     *
     * @param Transaction $transaction
     * @return float Calculated commission amount (rounded up to the nearest cent)
     * @throws BinLookupException
     * @throws ExchangeRateException
     */
    public function calculateCommission(Transaction $transaction): float
    {
        $countryCode = $this->binLookupService->getCountryCodeByBin($transaction->getBin());
        $isEu = $this->euCountryValidator->isEuCountry($countryCode);
        $rate = $this->getExchangeRate($transaction->getCurrency());

        $amountInEur = $this->convertToEur($transaction->getAmount(), $transaction->getCurrency(), $rate);
        $commission = $amountInEur * ($isEu ? self::EU_COMMISSION_RATE : self::NON_EU_COMMISSION_RATE);

        // Ceiling to the nearest cent (new requirement)
        return ceil($commission * 100) / 100;
    }

    /**
     * Convert an amount to EUR based on the currency and exchange rate
     *
     * @param float $amount
     * @param string $currency
     * @param float|null $rate
     * @return float
     */
    private function convertToEur(float $amount, string $currency, ?float $rate): float
    {
        if ($currency === self::BASE_CURRENCY || !$rate) {
            return $amount;
        }

        return $amount / $rate;
    }

    /**
     * Get the exchange rate for a currency
     *
     * @param string $currency
     * @return float|null
     * @throws ExchangeRateException
     */
    private function getExchangeRate(string $currency): ?float
    {
        if ($currency === self::BASE_CURRENCY) {
            return 1.0;
        }

        return $this->exchangeRateService->getRate($currency);
    }

    /**
     * Read file contents
     *
     * @param string $filePath
     * @return string
     * @throws FileReadException
     */
    private function readFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new FileReadException("File not found: {$filePath}");
        }

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new FileReadException("Unable to read file: {$filePath}");
        }

        return $contents;
    }

    /**
     * Parse transaction JSON
     *
     * @param string $json
     * @return Transaction
     * @throws JsonParseException
     */
    private function parseTransaction(string $json): Transaction
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonParseException("Invalid JSON: " . json_last_error_msg());
        }

        if (!isset($data['bin'], $data['amount'], $data['currency'])) {
            throw new JsonParseException("Missing required transaction data");
        }

        return new Transaction(
            $data['bin'],
            (float) $data['amount'],
            $data['currency']
        );
    }
}
