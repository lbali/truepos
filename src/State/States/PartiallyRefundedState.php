<?php

declare(strict_types=1);

namespace TruePos\State\States;

use TruePos\Enums\TransactionStatus;
use TruePos\State\TransactionStateMachine;

final class PartiallyRefundedState extends TransactionState
{
    public function status(): TransactionStatus
    {
        return TransactionStatus::PartiallyRefunded;
    }

    public function refund(TransactionStateMachine $machine): void
    {
        $machine->transitionTo(new RefundedState());
    }

    public function partialRefund(TransactionStateMachine $machine): void
    {
        // Stay in partially refunded — another partial refund
    }
}
