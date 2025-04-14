<?php

declare(strict_types=1);

namespace App\Tests\Service\BinLookup;

use App\Exception\BinLookupException;
use App\Service\BinLookup\BinListNetService;
use Mockery;
use PHPUnit\Framework\TestCase;

class BinListNetServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetCountryCodeByBin(): void
    {
        // Create a mock that will override file_get_contents
        $mockService = Mockery::mock(BinListNetService::class)->makePartial();
        $mockService->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('makeApiRequest')
            ->with('45717360')
            ->andReturn('{"number":{},"scheme":"visa","type":"credit","brand":"Visa","prepaid":false,"country":{"numeric":"276","alpha2":"DE","name":"Germany","emoji":"🇩🇪","currency":"EUR","latitude":51,"longitude":9},"bank":{}}');

        $countryCode = $mockService->getCountryCodeByBin('45717360');
        $this->assertEquals('DE', $countryCode);
    }

    public function testGetCountryCodeByBinNoResponse(): void
    {
        $mockService = Mockery::mock(BinListNetService::class)->makePartial();
        $mockService->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('makeApiRequest')
            ->with('45717360')
            ->andReturn('');

        $this->expectException(BinLookupException::class);
        $mockService->getCountryCodeByBin('45717360');
    }

    public function testGetCountryCodeByBinInvalidJson(): void
    {
        $mockService = Mockery::mock(BinListNetService::class)->makePartial();
        $mockService->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('makeApiRequest')
            ->with('45717360')
            ->andReturn('{invalid json}');

        $this->expectException(BinLookupException::class);
        $mockService->getCountryCodeByBin('45717360');
    }

    public function testGetCountryCodeByBinNoCountryData(): void
    {
        $mockService = Mockery::mock(BinListNetService::class)->makePartial();
        $mockService->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('makeApiRequest')
            ->with('45717360')
            ->andReturn('{"number":{},"scheme":"visa","type":"credit"}');

        $this->expectException(BinLookupException::class);
        $mockService->getCountryCodeByBin('45717360');
    }
}
