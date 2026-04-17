<?php

declare(strict_types=1);

namespace TruePos\Gateways\PayTR;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * PayTR returns JSON responses.
 * iframe token endpoint: {"status": "success", "token": "..."}
 * Direct API: {"status": "success"} or {"status": "failed", "err_msg": "..."}
 */
final class PayTRResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $status = $rawResponse['status'] ?? '';
        $isApproved = $status === 'success';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::PayTR,
            transactionType: $type,
            transactionId: $rawResponse['trans_id'] ?? null,
            orderId: $rawResponse['merchant_oid'] ?? null,
            authCode: null,
            responseCode: $isApproved ? '00' : ($rawResponse['err_no'] ?? null),
            responseMessage: $rawResponse['reason'] ?? $status,
            errorCode: $isApproved ? null : ($rawResponse['err_no'] ?? 'FAILED'),
            errorMessage: $isApproved ? null : ($rawResponse['err_msg'] ?? $rawResponse['reason'] ?? null),
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
            gateway: Gateway::PayTR,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['trans_id'] ?? null,
            orderId: $callbackData['merchant_oid'] ?? null,
            authCode: null,
            responseCode: $isApproved ? '00' : ($callbackData['failed_reason_code'] ?? null),
            responseMessage: $callbackData['failed_reason_msg'] ?? $status,
            errorCode: $isApproved ? null : ($callbackData['failed_reason_code'] ?? null),
            errorMessage: $isApproved ? null : ($callbackData['failed_reason_msg'] ?? null),
            rawResponse: $callbackData,
        );
    }
}
