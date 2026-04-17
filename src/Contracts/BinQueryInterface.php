<?php

declare(strict_types=1);

namespace TruePos\Contracts;

use TruePos\DataTransferObjects\BinQueryResult;

interface BinQueryInterface
{
    /**
     * Query installment rates and card info by BIN (first 6 digits).
     */
    public function queryBin(string $bin): BinQueryResult;
}
