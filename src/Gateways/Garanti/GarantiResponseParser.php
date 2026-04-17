<?php

declare(strict_types=1);

namespace TruePos\Gateways\Garanti;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

final class GarantiResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $transaction = $rawResponse['Transaction'] ?? [];
        $order = $rawResponse['Order'] ?? [];

        $responseCode = $transaction['Response']['Code'] ?? '';
        $isApproved = $responseCode === '00';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Garanti,
            transactionType: $type,
            transactionId: $transaction['RetrefNum'] ?? null,
            orderId: $order['OrderID'] ?? null,
            authCode: $transaction['AuthCode'] ?? null,
            responseCode: $responseCode,
            responseMessage: $transaction['Response']['Message'] ?? null,
            errorCode: $isApproved ? null : ($transaction['Response']['ErrorCode'] ?? $responseCode),
            errorMessage: $isApproved ? null : ($transaction['Response']['SysErrMsg'] ?? $transaction['Response']['Message'] ?? null),
            hostReferenceNumber: $transaction['RetrefNum'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $responseCode = $callbackData['procreturncode'] ?? '';
        $isApproved = $responseCode === '00';
        $mdStatus = $callbackData['mdstatus'] ?? null;

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Garanti,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['transid'] ?? null,
            orderId: $callbackData['orderid'] ?? null,
            authCode: $callbackData['authcode'] ?? null,
            responseCode: $responseCode,
            responseMessage: $callbackData['response'] ?? null,
            errorCode: $isApproved ? null : ($callbackData['errmsg'] ?? null),
            errorMessage: $isApproved ? null : ($callbackData['errmsg'] ?? null),
            hostReferenceNumber: $callbackData['hostrefnum'] ?? null,
            mdStatus: $mdStatus,
            rawResponse: $callbackData,
        );
    }
}
