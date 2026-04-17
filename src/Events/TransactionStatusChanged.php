<?php

declare(strict_types=1);

namespace TruePos\Events;

use TruePos\Enums\TransactionStatus;

final readonly class TransactionStatusChanged
{

    public function __construct(
        public TransactionStatus $from,
        public TransactionStatus $to,
    ) {}
}
