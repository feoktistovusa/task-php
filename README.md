# Transaction Commission Calculator

A PHP application for calculating commissions on credit card transactions based on the country of card issuance.

## Requirements

- PHP 7.4 or higher
- Composer

## Installation

1. Clone the repository
2. Install dependencies:
```
composer install
```

## Usage

The application takes a file path as an argument and processes transactions in the file:

```
php app.php input.txt
```

Each line in the input file should contain a JSON object with:
- `bin`: The Bank Identification Number of the card
- `amount`: The transaction amount
- `currency`: The transaction currency code (e.g., EUR, USD)

Example input format:
```
{"bin":"45717360","amount":"100.00","currency":"EUR"}
{"bin":"516793","amount":"50.00","currency":"USD"}
```

## Code Structure

The application follows SOLID principles with dependency injection:

- **TransactionProcessor**: Main class that orchestrates the processing of transactions
- **Service Interfaces**:
    - **BinLookupServiceInterface**: For resolving country information from BIN
    - **ExchangeRateServiceInterface**: For retrieving currency exchange rates
    - **EuCountryValidatorInterface**: For checking if a country is in the EU
- **Service Implementations**:
    - **BinListNetService**: Implementation using binlist.net API
    - **ExchangeRatesApiService**: Implementation using exchangeratesapi.io
    - **EuCountryValidator**: Local implementation with EU country codes

## Tests

Run the test suite with:
```
vendor/bin/phpunit
```

Tests use mocking to ensure they pass even when remote services change.

## Features

- Calculates commissions at 1% for EU-issued cards and 2% for non-EU cards
- Converts all amounts to EUR for calculation
- Rounds up commissions to the nearest cent
- Error handling for API failures and invalid data
- Unit tests with mocking of external services

## Extensibility

The code is designed to be easily extended:
- To change the BIN provider, create a new implementation of `BinLookupServiceInterface`
- To change the exchange rate provider, create a new implementation of `ExchangeRateServiceInterface`
- Additional services can be injected into the processor as needed
