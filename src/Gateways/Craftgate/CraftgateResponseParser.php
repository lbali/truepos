<?php

declare(strict_types=1);

namespace TruePos\Gateways\Craftgate;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * Craftgate JSON responses contain:
 * - data.paymentStatus: SUCCESS | FAILURE | INIT_THREEDS | WAITING
 * - data.id: payment ID
 * - data.conversationId: merchant order ID
 * - errors[]: error objects with code and description
 */
final class CraftgateResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $data = $rawResponse['data'] ?? $rawResponse;
        $errors = $rawResponse['errors'] ?? null;

        $isApproved = $errors === null
            && (($data['paymentStatus'] ?? '') === 'SUCCESS'
                || ($data['refundStatus'] ?? '') === 'SUCCESS');

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Craftgate,
            transactionType: $type,
            transactionId: isset($data['id']) ? (string) $data['id'] : null,
            orderId: $data['conversationId'] ?? null,
            authCode: $data['authCode'] ?? null,
            responseCode: $isApproved ? '00' : ($errors[0]['errorCode'] ?? null),
            responseMessage: $isApproved ? 'Success' : ($errors[0]['errorDescription'] ?? null),
            errorCode: $isApproved ? null : ($errors[0]['errorCode'] ?? null),
            errorMessage: $isApproved ? null : ($errors[0]['errorDescription'] ?? null),
            hostReferenceNumber: $data['hostReference'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $status = $callbackData['status'] ?? '';
        $isApproved = $status === 'SUCCESS';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Craftgate,
            transactionType: TransactionType::Purchase,
            transactionId: isset($callbackData['paymentId']) ? (string) $callbackData['paymentId'] : null,
            orderId: $callbackData['conversationId'] ?? null,
            responseCode: $isApproved ? '00' : ($callbackData['callbackStatus'] ?? null),
            responseMessage: $status,
            errorCode: $isApproved ? null : ($callbackData['callbackStatus'] ?? null),
            errorMessage: $isApproved ? null : $status,
            mdStatus: $callbackData['completeStatus'] ?? null,
            rawResponse: $callbackData,
        );
    }
}
