<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Processor\TransactionProcessor;
use Service\BinLookup\BinListNetService;
use Service\CountryValidator\EuCountryValidator;
use Service\ExchangeRate\ExchangeRatesApiService;

// Check if the input file is provided
if ($argc < 2) {
    die("Usage: php app.php input_file.txt\n");
}

$filePath = $argv[1];

// Create dependencies
$binLookupService = new BinListNetService();
$exchangeRateService = new ExchangeRatesApiService();
$euCountryValidator = new EuCountryValidator();

// Create transaction processor
$processor = new TransactionProcessor(
    $binLookupService,
    $exchangeRateService,
    $euCountryValidator
);

try {
    // Process file and get commissions
    $commissions = $processor->processFile($filePath);

    // Output results
    foreach ($commissions as $commission) {
        echo $commission . PHP_EOL;
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . PHP_EOL);
}
