<?php

declare(strict_types=1);

namespace TruePos\Gateways\Iyzico;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * iyzico JSON response parser.
 *
 * Success: {"status": "success", "paymentId": "...", "authCode": "..."}
 * Failure: {"status": "failure", "errorCode": "...", "errorMessage": "..."}
 */
final class IyzicoResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $status = $rawResponse['status'] ?? '';
        $isApproved = $status === 'success';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Iyzico,
            transactionType: $type,
            transactionId: $rawResponse['paymentId'] ?? null,
            orderId: $rawResponse['basketId'] ?? null,
            authCode: $rawResponse['authCode'] ?? null,
            responseCode: $isApproved ? '00' : ($rawResponse['errorCode'] ?? null),
            responseMessage: $rawResponse['errorMessage'] ?? $status,
            errorCode: $isApproved ? null : ($rawResponse['errorCode'] ?? null),
            errorMessage: $isApproved ? null : ($rawResponse['errorMessage'] ?? null),
            hostReferenceNumber: $rawResponse['hostReference'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $status = $callbackData['status'] ?? '';
        $isApproved = $status === 'success';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Iyzico,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['paymentId'] ?? null,
            orderId: $callbackData['basketId'] ?? null,
            authCode: $callbackData['authCode'] ?? null,
            responseCode: $isApproved ? '00' : ($callbackData['errorCode'] ?? null),
            responseMessage: $callbackData['errorMessage'] ?? $status,
            errorCode: $isApproved ? null : ($callbackData['errorCode'] ?? null),
            errorMessage: $isApproved ? null : ($callbackData['errorMessage'] ?? null),
            mdStatus: $callbackData['mdStatus'] ?? null,
            rawResponse: $callbackData,
        );
    }
}
