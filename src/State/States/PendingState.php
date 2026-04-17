<?php

declare(strict_types=1);

namespace TruePos\State\States;

use TruePos\Enums\TransactionStatus;
use TruePos\State\TransactionStateMachine;

final class PendingState extends TransactionState
{
    public function status(): TransactionStatus
    {
        return TransactionStatus::Pending;
    }

    public function process(TransactionStateMachine $machine): void
    {
        $machine->transitionTo(new ProcessingState());
    }

    public function complete(TransactionStateMachine $machine): void
    {
        $machine->transitionTo(new CompletedState());
    }

    public function fail(TransactionStateMachine $machine): void
    {
        $machine->transitionTo(new FailedState());
    }

    public function cancel(TransactionStateMachine $machine): void
    {
        $machine->transitionTo(new CancelledState());
    }
}
