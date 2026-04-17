<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\State;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\TransactionStatus;
use TruePos\Exceptions\InvalidStateTransitionException;
use TruePos\State\TransactionStateMachine;

final class TransactionStateMachineTest extends TestCase
{
    #[Test]
    public function it_starts_in_pending_state(): void
    {
        $machine = new TransactionStateMachine();

        $this->assertSame(TransactionStatus::Pending, $machine->currentStatus());
    }

    #[Test]
    public function it_transitions_pending_to_processing(): void
    {
        $machine = new TransactionStateMachine();
        $machine->process();

        $this->assertSame(TransactionStatus::Processing, $machine->currentStatus());
    }

    #[Test]
    public function it_transitions_pending_to_completed(): void
    {
        $machine = new TransactionStateMachine();
        $machine->complete();

        $this->assertSame(TransactionStatus::Completed, $machine->currentStatus());
    }

    #[Test]
    public function it_transitions_pending_to_failed(): void
    {
        $machine = new TransactionStateMachine();
        $machine->fail();

        $this->assertSame(TransactionStatus::Failed, $machine->currentStatus());
    }

    #[Test]
    public function it_transitions_processing_to_completed(): void
    {
        $machine = new TransactionStateMachine();
        $machine->process();
        $machine->complete();

        $this->assertSame(TransactionStatus::Completed, $machine->currentStatus());
    }

    #[Test]
    public function it_transitions_completed_to_refunded(): void
    {
        $machine = new TransactionStateMachine();
        $machine->complete();
        $machine->refund();

        $this->assertSame(TransactionStatus::Refunded, $machine->currentStatus());
    }

    #[Test]
    public function it_transitions_completed_to_cancelled(): void
    {
        $machine = new TransactionStateMachine();
        $machine->complete();
        $machine->cancel();

        $this->assertSame(TransactionStatus::Cancelled, $machine->currentStatus());
    }

    #[Test]
    public function it_transitions_completed_to_partially_refunded(): void
    {
        $machine = new TransactionStateMachine();
        $machine->complete();
        $machine->partialRefund();

        $this->assertSame(TransactionStatus::PartiallyRefunded, $machine->currentStatus());
    }

    #[Test]
    public function it_transitions_partially_refunded_to_refunded(): void
    {
        $machine = new TransactionStateMachine();
        $machine->complete();
        $machine->partialRefund();
        $machine->refund();

        $this->assertSame(TransactionStatus::Refunded, $machine->currentStatus());
    }

    #[Test]
    public function it_rejects_invalid_transition_from_failed(): void
    {
        $this->expectException(InvalidStateTransitionException::class);

        $machine = new TransactionStateMachine();
        $machine->fail();
        $machine->complete();
    }

    #[Test]
    public function it_rejects_refund_on_pending(): void
    {
        $this->expectException(InvalidStateTransitionException::class);

        $machine = new TransactionStateMachine();
        $machine->refund();
    }

    #[Test]
    public function it_rejects_process_on_completed(): void
    {
        $this->expectException(InvalidStateTransitionException::class);

        $machine = new TransactionStateMachine();
        $machine->complete();
        $machine->process();
    }
}
