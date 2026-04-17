<?php

declare(strict_types=1);

namespace TruePos\Repositories;

use TruePos\Contracts\TransactionRepositoryInterface;
use TruePos\DataTransferObjects\PaymentResponse;

final class NullTransactionRepository implements TransactionRepositoryInterface
{
    public function save(PaymentResponse $response): void
    {
        // Intentionally empty — Null Object pattern.
    }

    public function findByOrderId(string $orderId): ?PaymentResponse
    {
        return null;
    }

    public function findByTransactionId(string $transactionId): ?PaymentResponse
    {
        return null;
    }
}
