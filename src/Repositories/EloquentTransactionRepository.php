<?php

declare(strict_types=1);

namespace TruePos\Repositories;

use TruePos\Contracts\TransactionRepositoryInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Models\Transaction;
use TruePos\Support\SensitiveDataRedactor;

final class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    public function save(PaymentResponse $response): void
    {
        Transaction::create([
            'order_id' => $response->orderId,
            'transaction_id' => $response->transactionId,
            'gateway' => $response->gateway->value,
            'transaction_type' => $response->transactionType->value,
            'status' => $response->status->value,
            'amount' => $response->amount?->amount ?? 0,
            'currency' => $response->amount?->currency->value ?? '949',
            'auth_code' => $response->authCode,
            'response_code' => $response->responseCode,
            'response_message' => $response->responseMessage,
            'error_code' => $response->errorCode,
            'error_message' => $response->errorMessage,
            'host_reference_number' => $response->hostReferenceNumber,
            'md_status' => $response->mdStatus,
            'raw_response' => SensitiveDataRedactor::redact($response->rawResponse),
        ]);
    }

    public function findByOrderId(string $orderId): ?PaymentResponse
    {
        $transaction = Transaction::where('order_id', $orderId)->latest()->first();

        return $transaction ? $this->toPaymentResponse($transaction) : null;
    }

    public function findByTransactionId(string $transactionId): ?PaymentResponse
    {
        $transaction = Transaction::where('transaction_id', $transactionId)->first();

        return $transaction ? $this->toPaymentResponse($transaction) : null;
    }

    private function toPaymentResponse(Transaction $transaction): PaymentResponse
    {
        return new PaymentResponse(
            isSuccessful: $transaction->status === \TruePos\Enums\TransactionStatus::Completed,
            status: $transaction->status,
            gateway: $transaction->gateway,
            transactionType: $transaction->transaction_type,
            transactionId: $transaction->transaction_id,
            orderId: $transaction->order_id,
            authCode: $transaction->auth_code,
            responseCode: $transaction->response_code,
            responseMessage: $transaction->response_message,
            errorCode: $transaction->error_code,
            errorMessage: $transaction->error_message,
            hostReferenceNumber: $transaction->host_reference_number,
            mdStatus: $transaction->md_status,
            rawResponse: $transaction->raw_response ?? [],
        );
    }
}
