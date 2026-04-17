<?php

declare(strict_types=1);

namespace TruePos\Gateways\NestPay;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\ValueObjects\Money;

final class NestPayResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $isApproved = ($rawResponse['Response'] ?? '') === 'Approved';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::NestPay,
            transactionType: $type,
            transactionId: $rawResponse['TransId'] ?? null,
            orderId: $rawResponse['OrderId'] ?? null,
            authCode: $rawResponse['AuthCode'] ?? null,
            responseCode: $rawResponse['ProcReturnCode'] ?? null,
            responseMessage: $rawResponse['Response'] ?? null,
            errorCode: $isApproved ? null : ($rawResponse['ErrCode'] ?? $rawResponse['ProcReturnCode'] ?? null),
            errorMessage: $isApproved ? null : ($rawResponse['ErrMsg'] ?? $rawResponse['Response'] ?? null),
            hostReferenceNumber: $rawResponse['HostRefNum'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $isApproved = ($callbackData['Response'] ?? '') === 'Approved'
            || ($callbackData['ProcReturnCode'] ?? '') === '00';

        $mdStatus = $callbackData['mdStatus'] ?? null;

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::NestPay,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['TransId'] ?? null,
            orderId: $callbackData['oid'] ?? null,
            authCode: $callbackData['AuthCode'] ?? null,
            responseCode: $callbackData['ProcReturnCode'] ?? null,
            responseMessage: $callbackData['Response'] ?? null,
            errorCode: $isApproved ? null : ($callbackData['ErrCode'] ?? null),
            errorMessage: $isApproved ? null : ($callbackData['ErrMsg'] ?? null),
            hostReferenceNumber: $callbackData['HostRefNum'] ?? null,
            mdStatus: $mdStatus,
            rawResponse: $callbackData,
        );
    }
}
