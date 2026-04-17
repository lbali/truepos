<?php

declare(strict_types=1);

namespace TruePos\State\States;

use TruePos\Enums\TransactionStatus;
use TruePos\State\TransactionStateMachine;

final class ProcessingState extends TransactionState
{
    public function status(): TransactionStatus
    {
        return TransactionStatus::Processing;
    }

    public function complete(TransactionStateMachine $machine): void
    {
        $machine->transitionTo(new CompletedState());
    }

    public function fail(TransactionStateMachine $machine): void
    {
        $machine->transitionTo(new FailedState());
    }
}
