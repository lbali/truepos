<?php

declare(strict_types=1);

namespace TruePos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TruePos\Enums\TransactionStatus;

final readonly class TransactionStatusChanged
{
    use Dispatchable;

    public function __construct(
        public TransactionStatus $from,
        public TransactionStatus $to,
    ) {}
}
