<?php

declare(strict_types=1);

namespace TruePos\DataTransferObjects;

use TruePos\Enums\CardNetwork;
use TruePos\ValueObjects\Installment;

final readonly class BinQueryResult
{
    /**
     * @param  Installment[]  $installments
     */
    public function __construct(
        public string $bin,
        public CardNetwork $network,
        public ?string $bankName = null,
        public ?string $cardType = null,
        public ?string $cardFamily = null,
        public bool $isCommercial = false,
        public array $installments = [],
    ) {}
}
