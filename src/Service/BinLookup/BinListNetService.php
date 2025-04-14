<?php

declare(strict_types=1);

namespace App\Service\BinLookup;

use App\Exception\BinLookupException;

class BinListNetService implements BinLookupServiceInterface
{
    private const API_URL = 'https://lookup.binlist.net/';

    /**
     * @inheritDoc
     */
    public function getCountryCodeByBin(string $bin): string
    {
        $response = $this->makeApiRequest($bin);

        if (empty($response)) {
            throw new BinLookupException("Failed to get BIN data");
        }

        $binData = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BinLookupException("Invalid BIN data: " . json_last_error_msg());
        }

        if (!isset($binData->country->alpha2)) {
            throw new BinLookupException("Country code not found in BIN data");
        }

        return $binData->country->alpha2;
    }

    /**
     * Make API request to BIN lookup service
     *
     * @param string $bin
     * @return string
     * @throws BinLookupException
     */
    protected function makeApiRequest(string $bin): string
    {
        $url = self::API_URL . $bin;
        $response = @file_get_contents($url);

        if ($response === false) {
            throw new BinLookupException("Failed to connect to BIN service");
        }

        return $response;
    }
}
