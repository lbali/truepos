<?php

declare(strict_types=1);

namespace TruePos\Gateways\Lidio;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * Lidio response parser.
 *
 * Success: result=Success
 * 3DS redirect: result=RedirectFormCreated, resultDetail=ThreeDSRedirectFormCreated
 * Failure: result=Refused
 *
 * Payment info nested under paymentInfo object.
 */
final class LidioResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $result = $rawResponse['result'] ?? '';
        $isApproved = $result === 'Success';
        $paymentInfo = $rawResponse['paymentInfo'] ?? [];

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Lidio,
            transactionType: $type,
            transactionId: $paymentInfo['systemTransId'] ?? null,
            orderId: $paymentInfo['orderId'] ?? null,
            authCode: $paymentInfo['instrumentDetail']['acquirerResultDetail']['authCode'] ?? null,
            responseCode: $isApproved ? '00' : ($rawResponse['resultDetail'] ?? $result),
            responseMessage: $rawResponse['resultMessage'] ?? $result,
            errorCode: $isApproved ? null : ($rawResponse['resultDetail'] ?? $result),
            errorMessage: $isApproved ? null : ($rawResponse['resultMessage'] ?? null),
            hostReferenceNumber: $paymentInfo['systemTransId'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $result = $callbackData['Result'] ?? $callbackData['result'] ?? '';
        $isApproved = $result === '3DSuccess';

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Processing : TransactionStatus::Failed,
            gateway: Gateway::Lidio,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['SystemTransId'] ?? $callbackData['systemTransId'] ?? null,
            orderId: $callbackData['OrderId'] ?? $callbackData['orderId'] ?? null,
            responseCode: $isApproved ? '00' : 'FAILED',
            responseMessage: $result,
            errorCode: $isApproved ? null : 'ThreeDFailed',
            errorMessage: $isApproved ? null : '3D Secure verification failed',
            mdStatus: $callbackData['MDStatus'] ?? $callbackData['mdStatus'] ?? null,
            rawResponse: $callbackData,
        );
    }
}
