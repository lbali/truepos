<?php

declare(strict_types=1);

namespace TruePos\State\States;

use TruePos\Enums\TransactionStatus;
use TruePos\Exceptions\InvalidStateTransitionException;
use TruePos\State\TransactionStateMachine;

abstract class TransactionState
{
    abstract public function status(): TransactionStatus;

    public function process(TransactionStateMachine $machine): void
    {
        throw InvalidStateTransitionException::cannot($this->status(), TransactionStatus::Processing);
    }

    public function complete(TransactionStateMachine $machine): void
    {
        throw InvalidStateTransitionException::cannot($this->status(), TransactionStatus::Completed);
    }

    public function fail(TransactionStateMachine $machine): void
    {
        throw InvalidStateTransitionException::cannot($this->status(), TransactionStatus::Failed);
    }

    public function refund(TransactionStateMachine $machine): void
    {
        throw InvalidStateTransitionException::cannot($this->status(), TransactionStatus::Refunded);
    }

    public function partialRefund(TransactionStateMachine $machine): void
    {
        throw InvalidStateTransitionException::cannot($this->status(), TransactionStatus::PartiallyRefunded);
    }

    public function cancel(TransactionStateMachine $machine): void
    {
        throw InvalidStateTransitionException::cannot($this->status(), TransactionStatus::Cancelled);
    }
}
