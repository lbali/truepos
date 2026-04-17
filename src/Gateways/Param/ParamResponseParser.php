<?php

declare(strict_types=1);

namespace TruePos\Gateways\Param;

use TruePos\Contracts\ResponseParserInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * Param JSON response:
 * {"Sonuc": 1, "Sonuc_Str": "Başarılı", "Dekont_ID": "...", ...}
 * {"Sonuc": 0, "Sonuc_Str": "Hatalı İşlem", "Hata_Kodu": "...", ...}
 */
final class ParamResponseParser implements ResponseParserInterface
{
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse
    {
        $result = (int) ($rawResponse['Sonuc'] ?? $rawResponse['RESULT'] ?? 0);
        $isApproved = $result === 1;

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Param,
            transactionType: $type,
            transactionId: $rawResponse['Dekont_ID'] ?? $rawResponse['TRANSACTION_ID'] ?? null,
            orderId: $rawResponse['Siparis_ID'] ?? $rawResponse['ORDER_ID'] ?? null,
            authCode: $rawResponse['Banka_Sonuc_Kod'] ?? null,
            responseCode: $isApproved ? '00' : ($rawResponse['Hata_Kodu'] ?? null),
            responseMessage: $rawResponse['Sonuc_Str'] ?? $rawResponse['RESULT_MESSAGE'] ?? null,
            errorCode: $isApproved ? null : ($rawResponse['Hata_Kodu'] ?? $rawResponse['ERROR_CODE'] ?? null),
            errorMessage: $isApproved ? null : ($rawResponse['Sonuc_Str'] ?? $rawResponse['ERROR_MESSAGE'] ?? null),
            rawResponse: $rawResponse,
        );
    }

    public function parseThreeDCallback(array $callbackData): PaymentResponse
    {
        $result = (int) ($callbackData['Sonuc'] ?? $callbackData['TURKPOS_RETVAL_Sonuc'] ?? 0);
        $isApproved = $result === 1;

        return new PaymentResponse(
            isSuccessful: $isApproved,
            status: $isApproved ? TransactionStatus::Completed : TransactionStatus::Failed,
            gateway: Gateway::Param,
            transactionType: TransactionType::Purchase,
            transactionId: $callbackData['Dekont_ID'] ?? $callbackData['TURKPOS_RETVAL_Dekont_ID'] ?? null,
            orderId: $callbackData['Siparis_ID'] ?? $callbackData['TURKPOS_RETVAL_Siparis_ID'] ?? null,
            authCode: $callbackData['Banka_Sonuc_Kod'] ?? null,
            responseCode: $isApproved ? '00' : null,
            responseMessage: $callbackData['Sonuc_Str'] ?? $callbackData['TURKPOS_RETVAL_Sonuc_Str'] ?? null,
            errorCode: $isApproved ? null : ($callbackData['Hata_Kodu'] ?? null),
            errorMessage: $isApproved ? null : ($callbackData['Sonuc_Str'] ?? null),
            mdStatus: $callbackData['mdStatus'] ?? null,
            rawResponse: $callbackData,
        );
    }
}
