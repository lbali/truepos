<?php

declare(strict_types=1);

namespace TruePos\Gateways\Moka;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * Moka JSON response:
 * {"ResultCode": "Success", "Data": {"VirtualPosOrderId": "...", ...}}
 * {"ResultCode": "PaymentDealer.CheckPaymentDealerAuthentication.InvalidRequest", ...}
 */
final class MokaResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $resultCode = $rawResponse['ResultCode'] ?? '';
        $isApproved = $resultCode === 'Success';
        $data = $rawResponse['Data'] ?? [];

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Moka,
            transactionType: $type,
            transactionId: $data['VirtualPosOrderId'] ?? null,
            orderId: $data['OtherTrxCode'] ?? null,
            authCode: $data['ApprovalCode'] ?? null,
            responseCode: $isApproved ? '00' : $resultCode,
            responseMessage: $rawResponse['ResultMessage'] ?? null,
            errorCode: $isApproved ? null : $resultCode,
            errorMessage: $isApproved ? null : ($rawResponse['ResultMessage'] ?? null),
            hostReferenceNumber: $data['VirtualPosOrderId'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $resultCode = $callbackData['resultCode'] ?? $callbackData['ResultCode'] ?? '';
        $isApproved = $resultCode === 'Success' || ($callbackData['isSuccessful'] ?? '') === '1';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Moka,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['trxCode'] ?? null,
            orderId: $callbackData['otherTrxCode'] ?? null,
            authCode: $callbackData['approvalCode'] ?? null,
            responseCode: $isApproved ? '00' : $resultCode,
            responseMessage: $callbackData['resultMessage'] ?? null,
            errorCode: $isApproved ? null : $resultCode,
            errorMessage: $isApproved ? null : ($callbackData['resultMessage'] ?? null),
            rawResponse: $callbackData,
        );
    }
}
