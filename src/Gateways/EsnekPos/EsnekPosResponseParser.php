<?php

declare(strict_types=1);

namespace TruePos\Gateways\EsnekPos;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * EsnekPOS response parser.
 *
 * Success: STATUS=SUCCESS, RETURN_CODE=0
 * Failure: STATUS=FAILED, RETURN_CODE=error_code, RETURN_MESSAGE=description
 *
 * 3DS init returns URL_3DS for customer redirect.
 * Callback POSTs to BACK_URL with payment result.
 */
final class EsnekPosResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $status = $rawResponse['STATUS'] ?? '';
        $returnCode = $rawResponse['RETURN_CODE'] ?? '';
        $isApproved = $status === 'SUCCESS' && $returnCode === '0';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::EsnekPos,
            transactionType: $type,
            transactionId: $rawResponse['REFNO'] ?? null,
            orderId: $rawResponse['ORDER_REF_NUMBER'] ?? null,
            authCode: $rawResponse['BANK_AUTH_CODE'] ?? null,
            responseCode: $returnCode,
            responseMessage: $rawResponse['RETURN_MESSAGE'] ?? null,
            errorCode: $isApproved ? null : $returnCode,
            errorMessage: $isApproved ? null : ($rawResponse['RETURN_MESSAGE'] ?? null),
            hostReferenceNumber: $rawResponse['REFNO'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $status = $callbackData['STATUS'] ?? '';
        $returnCode = $callbackData['RETURN_CODE'] ?? '';
        $isApproved = $status === 'SUCCESS' && $returnCode === '0';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::EsnekPos,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['REFNO'] ?? null,
            orderId: $callbackData['ORDER_REF_NUMBER'] ?? null,
            authCode: $callbackData['BANK_AUTH_CODE'] ?? null,
            responseCode: $returnCode,
            responseMessage: $callbackData['RETURN_MESSAGE'] ?? null,
            errorCode: $isApproved ? null : $returnCode,
            errorMessage: $isApproved ? null : ($callbackData['RETURN_MESSAGE'] ?? null),
            hostReferenceNumber: $callbackData['REFNO'] ?? null,
            mdStatus: $callbackData['IS_NOT_3D_PAYMENT'] ?? null,
            rawResponse: $callbackData,
        );
    }
}
