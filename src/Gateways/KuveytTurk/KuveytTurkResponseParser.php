<?php

declare(strict_types=1);

namespace TruePos\Gateways\KuveytTurk;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

final class KuveytTurkResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $responseCode = $rawResponse['ResponseCode'] ?? '';
        $isApproved = $responseCode === '00';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::KuveytTurk,
            transactionType: $type,
            transactionId: $rawResponse['ProvisionNumber'] ?? null,
            orderId: $rawResponse['MerchantOrderId'] ?? null,
            authCode: $rawResponse['ProvisionNumber'] ?? null,
            responseCode: $responseCode,
            responseMessage: $rawResponse['ResponseMessage'] ?? null,
            errorCode: $isApproved ? null : $responseCode,
            errorMessage: $isApproved ? null : ($rawResponse['ResponseMessage'] ?? null),
            hostReferenceNumber: $rawResponse['RRN'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $responseCode = $callbackData['ResponseCode'] ?? '';
        $isApproved = $responseCode === '00';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::KuveytTurk,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['ProvisionNumber'] ?? null,
            orderId: $callbackData['MerchantOrderId'] ?? null,
            authCode: $callbackData['ProvisionNumber'] ?? null,
            responseCode: $responseCode,
            responseMessage: $callbackData['ResponseMessage'] ?? null,
            errorCode: $isApproved ? null : $responseCode,
            errorMessage: $isApproved ? null : ($callbackData['ResponseMessage'] ?? null),
            mdStatus: $callbackData['MD'] ?? null,
            rawResponse: $callbackData,
        );
    }
}
