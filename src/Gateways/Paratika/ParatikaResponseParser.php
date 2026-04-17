<?php

declare(strict_types=1);

namespace TruePos\Gateways\Paratika;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * Paratika response parser.
 *
 * Success: responseCode=00, responseMsg=Approved
 * Session: sessionToken returned for HPP/Direct POST
 * Failure: responseCode != 00
 */
final class ParatikaResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $responseCode = $rawResponse['responseCode'] ?? '';
        $isApproved = $responseCode === '00';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Paratika,
            transactionType: $type,
            transactionId: $rawResponse['pgTranId'] ?? null,
            orderId: $rawResponse['merchantPaymentId'] ?? null,
            authCode: $rawResponse['pgTranApprCode'] ?? null,
            responseCode: $responseCode,
            responseMessage: $rawResponse['responseMsg'] ?? null,
            errorCode: $isApproved ? null : $responseCode,
            errorMessage: $isApproved ? null : ($rawResponse['errorMsg'] ?? $rawResponse['responseMsg'] ?? null),
            hostReferenceNumber: $rawResponse['pgTranRefId'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $responseCode = $callbackData['responseCode'] ?? '';
        $isApproved = $responseCode === '00';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Paratika,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['pgTranId'] ?? null,
            orderId: $callbackData['merchantPaymentId'] ?? null,
            authCode: $callbackData['pgTranApprCode'] ?? null,
            responseCode: $responseCode,
            responseMessage: $callbackData['responseMsg'] ?? null,
            errorCode: $isApproved ? null : $responseCode,
            errorMessage: $isApproved ? null : ($callbackData['errorMsg'] ?? null),
            hostReferenceNumber: $callbackData['pgTranRefId'] ?? null,
            mdStatus: $callbackData['mdStatus'] ?? null,
            rawResponse: $callbackData,
        );
    }
}
