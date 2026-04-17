<?php

declare(strict_types=1);

namespace TruePos\Gateways\PayFor;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

final class PayForResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $procReturnCode = $rawResponse['ProcReturnCode'] ?? '';
        $isApproved = $procReturnCode === '00';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::PayFor,
            transactionType: $type,
            transactionId: $rawResponse['TransId'] ?? null,
            orderId: $rawResponse['OrderId'] ?? null,
            authCode: $rawResponse['AuthCode'] ?? null,
            responseCode: $procReturnCode,
            responseMessage: $rawResponse['Response'] ?? null,
            errorCode: $isApproved ? null : ($rawResponse['ErrCode'] ?? $procReturnCode),
            errorMessage: $isApproved ? null : ($rawResponse['ErrMsg'] ?? null),
            hostReferenceNumber: $rawResponse['HostRefNum'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $procReturnCode = $callbackData['ProcReturnCode'] ?? '';
        $isApproved = $procReturnCode === '00';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::PayFor,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['TransId'] ?? null,
            orderId: $callbackData['OrderId'] ?? null,
            authCode: $callbackData['AuthCode'] ?? null,
            responseCode: $procReturnCode,
            responseMessage: $callbackData['Response'] ?? null,
            errorCode: $isApproved ? null : ($callbackData['ErrCode'] ?? null),
            errorMessage: $isApproved ? null : ($callbackData['ErrMsg'] ?? null),
            hostReferenceNumber: $callbackData['HostRefNum'] ?? null,
            mdStatus: $callbackData['3DStatus'] ?? null,
            rawResponse: $callbackData,
        );
    }
}
