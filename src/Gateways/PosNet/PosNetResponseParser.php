<?php

declare(strict_types=1);

namespace TruePos\Gateways\PosNet;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * PosNet response structure is very different from NestPay/Garanti:
 * - Success: <approved>1</approved> with <hostlogkey> as transaction ID
 * - Failure: <approved>0</approved> with <respCode> and <respText>
 */
final class PosNetResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $approved = ($rawResponse['approved'] ?? '0') === '1';

        return new PaymentResponse(
            isSuccessful: $approved,
            status: $approved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::PosNet,
            transactionType: $type,
            transactionId: $rawResponse['hostlogkey'] ?? null,
            orderId: $rawResponse['orderID'] ?? null,
            authCode: $rawResponse['authCode'] ?? null,
            responseCode: $rawResponse['respCode'] ?? ($approved ? '00' : null),
            responseMessage: $rawResponse['respText'] ?? ($approved ? 'Approved' : null),
            errorCode: $approved ? null : ($rawResponse['respCode'] ?? null),
            errorMessage: $approved ? null : ($rawResponse['respText'] ?? null),
            hostReferenceNumber: $rawResponse['hostlogkey'] ?? null,
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $approved = ($callbackData['approved'] ?? '0') === '1'
            || ($callbackData['MerchantPacket'] ?? '') !== '';

        $mdStatus = $callbackData['mdStatus'] ?? $callbackData['MdStatus'] ?? null;

        return new PaymentResponse(
            isSuccessful: $approved,
            status: $approved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::PosNet,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['hostlogkey'] ?? $callbackData['HostLogKey'] ?? null,
            orderId: $callbackData['XID'] ?? $callbackData['xid'] ?? null,
            authCode: $callbackData['authCode'] ?? $callbackData['AuthCode'] ?? null,
            responseCode: $callbackData['respCode'] ?? null,
            responseMessage: $callbackData['respText'] ?? null,
            errorCode: $approved ? null : ($callbackData['respCode'] ?? null),
            errorMessage: $approved ? null : ($callbackData['respText'] ?? null),
            mdStatus: $mdStatus,
            rawResponse: $callbackData,
        );
    }
}
