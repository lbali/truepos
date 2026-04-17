<?php

declare(strict_types=1);

namespace TruePos\State;

use TruePos\Enums\TransactionStatus;
use TruePos\Events\TransactionStatusChanged;
use TruePos\State\States\CancelledState;
use TruePos\State\States\CompletedState;
use TruePos\State\States\FailedState;
use TruePos\State\States\PartiallyRefundedState;
use TruePos\State\States\PendingState;
use TruePos\State\States\ProcessingState;
use TruePos\State\States\RefundedState;
use TruePos\State\States\TransactionState;

final class TransactionStateMachine
{
    private TransactionState $state;

    public function __construct(TransactionStatus $initial = TransactionStatus::Pending)
    {
        $this->state = self::stateFromEnum($initial);
    }

    public function currentStatus(): TransactionStatus
    {
        return $this->state->status();
    }

    public function transitionTo(TransactionState $newState): void
    {
        $previous = $this->state->status();
        $this->state = $newState;

        try {
            if (function_exists('event') && app()->bound('events')) {
                event(new TransactionStatusChanged($previous, $newState->status()));
            }
        } catch (\Throwable) {
            // Event dispatching is optional — works without Laravel container.
        }
    }

    public function process(): void
    {
        $this->state->process($this);
    }

    public function complete(): void
    {
        $this->state->complete($this);
    }

    public function fail(): void
    {
        $this->state->fail($this);
    }

    public function refund(): void
    {
        $this->state->refund($this);
    }

    public function partialRefund(): void
    {
        $this->state->partialRefund($this);
    }

    public function cancel(): void
    {
        $this->state->cancel($this);
    }

    private static function stateFromEnum(TransactionStatus $status): TransactionState
    {
        return match ($status) {
            TransactionStatus::Pending => new PendingState(),
            TransactionStatus::Processing => new ProcessingState(),
            TransactionStatus::Completed => new CompletedState(),
            TransactionStatus::Failed => new FailedState(),
            TransactionStatus::Refunded => new RefundedState(),
            TransactionStatus::PartiallyRefunded => new PartiallyRefundedState(),
            TransactionStatus::Cancelled => new CancelledState(),
        };
    }
}
