<?php

declare(strict_types=1);

namespace App\Tests;

use App\Exception\BinLookupException;
use App\Exception\ExchangeRateException;
use App\Model\Transaction;
use App\Service\BinLookup\BinLookupServiceInterface;
use App\Service\CountryValidator\EuCountryValidatorInterface;
use App\Service\ExchangeRate\ExchangeRateServiceInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Processor\TransactionProcessor;

class TransactionProcessorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCalculateCommissionForEuCountry(): void
    {
        // Mock services
        $binLookupService = Mockery::mock(BinLookupServiceInterface::class);
        $binLookupService->shouldReceive('getCountryCodeByBin')
            ->with('45717360')
            ->andReturn('DE');

        $exchangeRateService = Mockery::mock(ExchangeRateServiceInterface::class);
        $exchangeRateService->shouldReceive('getRate')
            ->with('EUR')
            ->andReturn(1.0);

        $euCountryValidator = Mockery::mock(EuCountryValidatorInterface::class);
        $euCountryValidator->shouldReceive('isEuCountry')
            ->with('DE')
            ->andReturn(true);

        // Create processor
        $processor = new TransactionProcessor(
            $binLookupService,
            $exchangeRateService,
            $euCountryValidator
        );

        // Create transaction
        $transaction = new Transaction('45717360', 100.00, 'EUR');

        // Calculate commission and assert result
        // 100 EUR * 0.01 (EU rate) = 1.00 EUR (no ceiling needed)
        $this->assertEquals(1.00, $processor->calculateCommission($transaction));
    }

    public function testCalculateCommissionForNonEuCountry(): void
    {
        // Mock services
        $binLookupService = Mockery::mock(BinLookupServiceInterface::class);
        $binLookupService->shouldReceive('getCountryCodeByBin')
            ->with('41417360')
            ->andReturn('US');

        $exchangeRateService = Mockery::mock(ExchangeRateServiceInterface::class);
        $exchangeRateService->shouldReceive('getRate')
            ->with('USD')
            ->andReturn(1.1);

        $euCountryValidator = Mockery::mock(EuCountryValidatorInterface::class);
        $euCountryValidator->shouldReceive('isEuCountry')
            ->with('US')
            ->andReturn(false);

        // Create processor
        $processor = new TransactionProcessor(
            $binLookupService,
            $exchangeRateService,
            $euCountryValidator
        );

        // Create transaction
        $transaction = new Transaction('41417360', 130.00, 'USD');

        // Calculate commission and assert result
        // 130 USD / 1.1 = 118.18 EUR * 0.02 (non-EU rate) = 2.3636 EUR
        // Ceiling to 2.37 EUR
        $this->assertEquals(2.37, $processor->calculateCommission($transaction));
    }

    public function testCalculateCommissionWithNoExchangeRate(): void
    {
        // Mock services
        $binLookupService = Mockery::mock(BinLookupServiceInterface::class);
        $binLookupService->shouldReceive('getCountryCodeByBin')
            ->with('516793')
            ->andReturn('RU');

        $exchangeRateService = Mockery::mock(ExchangeRateServiceInterface::class);
        $exchangeRateService->shouldReceive('getRate')
            ->with('USD')
            ->andReturn(null);

        $euCountryValidator = Mockery::mock(EuCountryValidatorInterface::class);
        $euCountryValidator->shouldReceive('isEuCountry')
            ->with('RU')
            ->andReturn(false);

        // Create processor
        $processor = new TransactionProcessor(
            $binLookupService,
            $exchangeRateService,
            $euCountryValidator
        );

        // Create transaction
        $transaction = new Transaction('516793', 50.00, 'USD');

        // Calculate commission and assert result
        // Since rate is null, amount stays 50.00 * 0.02 (non-EU rate) = 1.00 EUR (no ceiling needed)
        $this->assertEquals(1.00, $processor->calculateCommission($transaction));
    }

    public function testProcessFile(): void
    {
        // Create temporary file with test data
        $filePath = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($filePath, <<<EOF
{"bin":"45717360","amount":"100.00","currency":"EUR"}
{"bin":"516793","amount":"50.00","currency":"USD"}

EOF
        );

        // Mock services
        $binLookupService = Mockery::mock(BinLookupServiceInterface::class);
        $binLookupService->shouldReceive('getCountryCodeByBin')
            ->with('45717360')
            ->andReturn('DE');
        $binLookupService->shouldReceive('getCountryCodeByBin')
            ->with('516793')
            ->andReturn('US');

        $exchangeRateService = Mockery::mock(ExchangeRateServiceInterface::class);
        $exchangeRateService->shouldReceive('getRate')
            ->with('EUR')
            ->andReturn(1.0);
        $exchangeRateService->shouldReceive('getRate')
            ->with('USD')
            ->andReturn(1.1);

        $euCountryValidator = Mockery::mock(EuCountryValidatorInterface::class);
        $euCountryValidator->shouldReceive('isEuCountry')
            ->with('DE')
            ->andReturn(true);
        $euCountryValidator->shouldReceive('isEuCountry')
            ->with('US')
            ->andReturn(false);

        // Create processor
        $processor = new TransactionProcessor(
            $binLookupService,
            $exchangeRateService,
            $euCountryValidator
        );

        // Process file and assert results
        $results = $processor->processFile($filePath);
        $this->assertCount(2, $results);
        $this->assertEquals(1.00, $results[0]); // EU transaction
        $this->assertEquals(0.91, $results[1]); // Non-EU transaction, 50/1.1 * 0.02 = 0.9090... which rounds to 0.91

        // Clean up
        unlink($filePath);
    }

    public function testBinLookupFailure(): void
    {
        // Mock services with exception
        $binLookupService = Mockery::mock(BinLookupServiceInterface::class);
        $binLookupService->shouldReceive('getCountryCodeByBin')
            ->with('45717360')
            ->andThrow(new BinLookupException('Service unavailable'));

        $exchangeRateService = Mockery::mock(ExchangeRateServiceInterface::class);
        $euCountryValidator = Mockery::mock(EuCountryValidatorInterface::class);

        // Create processor
        $processor = new TransactionProcessor(
            $binLookupService,
            $exchangeRateService,
            $euCountryValidator
        );

        // Create transaction
        $transaction = new Transaction('45717360', 100.00, 'EUR');

        // Assert exception is thrown
        $this->expectException(BinLookupException::class);
        $processor->calculateCommission($transaction);
    }

    public function testExchangeRateFailure(): void
    {
        // Mock services with exception
        $binLookupService = Mockery::mock(BinLookupServiceInterface::class);
        $binLookupService->shouldReceive('getCountryCodeByBin')
            ->with('45717360')
            ->andReturn('DE');

        $exchangeRateService = Mockery::mock(ExchangeRateServiceInterface::class);
        $exchangeRateService->shouldReceive('getRate')
            ->with('USD')
            ->andThrow(new ExchangeRateException('Service unavailable'));

        $euCountryValidator = Mockery::mock(EuCountryValidatorInterface::class);
        $euCountryValidator->shouldReceive('isEuCountry')
            ->with('DE')
            ->andReturn(true);

        // Create processor
        $processor = new TransactionProcessor(
            $binLookupService,
            $exchangeRateService,
            $euCountryValidator
        );

        // Create transaction
        $transaction = new Transaction('45717360', 100.00, 'USD');

        // Assert exception is thrown
        $this->expectException(ExchangeRateException::class);
        $processor->calculateCommission($transaction);
    }
}
