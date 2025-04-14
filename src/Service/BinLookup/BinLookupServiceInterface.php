<?php

declare(strict_types=1);

namespace App\Service\BinLookup;

use App\Exception\BinLookupException;

interface BinLookupServiceInterface
{
    /**
     * Get country code by BIN
     *
     * @param string $bin
     * @return string ISO 3166-1 alpha-2 country code
     * @throws BinLookupException
     */
    public function getCountryCodeByBin(string $bin): string;
}
