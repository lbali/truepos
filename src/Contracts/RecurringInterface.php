<?php

declare(strict_types=1);

namespace TruePos\Contracts;

use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\PaymentResponse;

interface RecurringInterface
{
    public function recurringPurchase(PaymentRequest $request): PaymentResponse;

    public function cancelRecurring(string $recurringId): PaymentResponse;
}
