<?php

declare(strict_types=1);

namespace TruePos\Gateways\Sipay;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * Sipay JSON response:
 * {"status_code": 100, "status_description": "success", "data": {...}}
 */
final class SipayResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $statusCode = $rawResponse['status_code'] ?? 0;
        $isApproved = $statusCode === 100;
        $data = $rawResponse['data'] ?? [];

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Sipay,
            transactionType: $type,
            transactionId: $data['order_id'] ?? null,
            orderId: $data['invoice_id'] ?? null,
            authCode: $data['auth_code'] ?? null,
            responseCode: (string) $statusCode,
            responseMessage: $rawResponse['status_description'] ?? null,
            errorCode: $isApproved ? null : (string) $statusCode,
            errorMessage: $isApproved ? null : ($rawResponse['status_description'] ?? null),
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $statusCode = (int) ($callbackData['status_code'] ?? $callbackData['SipayStatus'] ?? 0);
        $isApproved = $statusCode === 100;

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Sipay,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['order_id'] ?? $callbackData['SipayOrderId'] ?? null,
            orderId: $callbackData['invoice_id'] ?? $callbackData['InvoiceId'] ?? null,
            authCode: $callbackData['auth_code'] ?? null,
            responseCode: (string) $statusCode,
            responseMessage: $callbackData['status_description'] ?? null,
            errorCode: $isApproved ? null : (string) $statusCode,
            errorMessage: $isApproved ? null : ($callbackData['status_description'] ?? null),
            rawResponse: $callbackData,
        );
    }
}
