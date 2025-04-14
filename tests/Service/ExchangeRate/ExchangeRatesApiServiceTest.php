<?php

declare(strict_types=1);

namespace App\Tests\Service\ExchangeRate;

use App\Exception\ExchangeRateException;
use App\Service\ExchangeRate\ExchangeRatesApiService;
use Mockery;
use PHPUnit\Framework\TestCase;

class ExchangeRatesApiServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetRate(): void
    {
        // Create a mock that will override file_get_contents
        $mockService = Mockery::mock(ExchangeRatesApiService::class)->makePartial();
        $mockService->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('makeApiRequest')
            ->andReturn('{"base":"EUR","date":"2023-04-01","rates":{"USD":1.1,"GBP":0.85,"JPY":132.5}}');

        $rate = $mockService->getRate('USD');
        $this->assertEquals(1.1, $rate);
    }

    public function testGetRateInvalidJson(): void
    {
        $mockService = Mockery::mock(ExchangeRatesApiService::class)->makePartial();
        $mockService->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('makeApiRequest')
            ->andReturn('{invalid json}');

        $this->expectException(ExchangeRateException::class);
        $mockService->getRate('USD');
    }

    public function testGetRateCurrencyNotFound(): void
    {
        $mockService = Mockery::mock(ExchangeRatesApiService::class)->makePartial();
        $mockService->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('makeApiRequest')
            ->andReturn('{"base":"EUR","date":"2023-04-01","rates":{"USD":1.1,"GBP":0.85}}');

        $this->expectException(ExchangeRateException::class);
        $mockService->getRate('XYZ');
    }

    public function testGetRateZeroRate(): void
    {
        $mockService = Mockery::mock(ExchangeRatesApiService::class)->makePartial();
        $mockService->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('makeApiRequest')
            ->andReturn('{"base":"EUR","date":"2023-04-01","rates":{"USD":0,"GBP":0.85}}');

        $this->assertNull($mockService->getRate('USD'));
    }
}
