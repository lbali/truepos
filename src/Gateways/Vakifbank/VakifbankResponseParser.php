<?php

declare(strict_types=1);

namespace TruePos\Gateways\Vakifbank;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

final class VakifbankResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $resultCode = $rawResponse['ResultCode'] ?? '';
        $isApproved = $resultCode === '0000';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Vakifbank,
            transactionType: $type,
            transactionId: $rawResponse['TransactionId'] ?? $rawResponse['Rrn'] ?? null,
            orderId: $rawResponse['OrderId'] ?? $rawResponse['MerchantOrderId'] ?? null,
            authCode: $rawResponse['AuthCode'] ?? null,
            responseCode: $resultCode,
            responseMessage: $rawResponse['ResultDetail'] ?? null,
            errorCode: $isApproved ? null : $resultCode,
            errorMessage: $isApproved ? null : ($rawResponse['ResultDetail'] ?? null),
            hostReferenceNumber: $rawResponse['Rrn'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $resultCode = $callbackData['ResultCode'] ?? '';
        $isApproved = $resultCode === '0000';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Vakifbank,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['TransactionId'] ?? null,
            orderId: $callbackData['MerchantOrderId'] ?? null,
            authCode: $callbackData['AuthCode'] ?? null,
            responseCode: $resultCode,
            responseMessage: $callbackData['ResultDetail'] ?? null,
            errorCode: $isApproved ? null : $resultCode,
            errorMessage: $isApproved ? null : ($callbackData['ResultDetail'] ?? null),
            mdStatus: $callbackData['MdStatus'] ?? null,
            rawResponse: $callbackData,
        );
    }
}
