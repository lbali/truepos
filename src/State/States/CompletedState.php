<?php

declare(strict_types=1);

namespace TruePos\State\States;

use TruePos\Enums\TransactionStatus;
use TruePos\State\TransactionStateMachine;

final class CompletedState extends TransactionState
{
    public function status(): TransactionStatus
    {
        return TransactionStatus::Completed;
    }

    public function refund(TransactionStateMachine $machine): void
    {
        $machine->transitionTo(new RefundedState());
    }

    public function partialRefund(TransactionStateMachine $machine): void
    {
        $machine->transitionTo(new PartiallyRefundedState());
    }

    public function cancel(TransactionStateMachine $machine): void
    {
        $machine->transitionTo(new CancelledState());
    }
}
