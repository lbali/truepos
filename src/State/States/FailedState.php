<?php

declare(strict_types=1);

namespace TruePos\State\States;

use TruePos\Enums\TransactionStatus;

final class FailedState extends TransactionState
{
    public function status(): TransactionStatus
    {
        return TransactionStatus::Failed;
    }
}
