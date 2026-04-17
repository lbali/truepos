<?php

declare(strict_types=1);

namespace TruePos\Contracts;

use TruePos\DataTransferObjects\PaymentResponse;

interface TransactionRepositoryInterface
{
    public function save(PaymentResponse $response): void;

    public function findByOrderId(string $orderId): ?PaymentResponse;

    public function findByTransactionId(string $transactionId): ?PaymentResponse;
}
