<?php

declare(strict_types=1);

namespace TruePos\Gateways\Tosla;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * Tosla JSON response parser.
 *
 * Success: {"Code": 0, "Message": "Success", "OrderId": "...", "TransactionId": "...", ...}
 * 3DS init: {"ThreeDSessionId": "...", "Code": 0}
 * Failure: {"Code": 1, "Message": "Error description"}
 */
final class ToslaResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $code = $rawResponse['Code'] ?? -1;
        $isApproved = $code === 0;

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Tosla,
            transactionType: $type,
            transactionId: $rawResponse['TransactionId'] ?? null,
            orderId: $rawResponse['OrderId'] ?? null,
            authCode: $rawResponse['AuthCode'] ?? null,
            responseCode: (string) $code,
            responseMessage: $rawResponse['Message'] ?? null,
            errorCode: $isApproved ? null : (string) $code,
            errorMessage: $isApproved ? null : ($rawResponse['Message'] ?? null),
            hostReferenceNumber: $rawResponse['TransactionId'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $code = (int) ($callbackData['Code'] ?? $callbackData['code'] ?? -1);
        $isApproved = $code === 0;

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Tosla,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['TransactionId'] ?? $callbackData['transactionId'] ?? null,
            orderId: $callbackData['OrderId'] ?? $callbackData['orderId'] ?? null,
            authCode: $callbackData['AuthCode'] ?? $callbackData['authCode'] ?? null,
            responseCode: (string) $code,
            responseMessage: $callbackData['Message'] ?? $callbackData['message'] ?? null,
            errorCode: $isApproved ? null : (string) $code,
            errorMessage: $isApproved ? null : ($callbackData['Message'] ?? $callbackData['message'] ?? null),
            mdStatus: $callbackData['MdStatus'] ?? $callbackData['mdStatus'] ?? null,
            rawResponse: $callbackData,
        );
    }
}
