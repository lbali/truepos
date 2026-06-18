<?php

declare(strict_types=1);

namespace TruePos\Contracts;

use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\DataTransferObjects\StoredCardChargeRequest;

/**
 * Kart saklama / card-on-file yeteneği — ThreeDSecureInterface gibi opsiyonel
 * bir capability. Yalnız tokenizasyonu destekleyen gateway'ler implement eder
 * (örn. iyzico).
 *
 * Akış:
 *  1. Kartı sakla: normal bir ödemede PaymentRequest::$storeCard = true ver
 *     (builder ->storeCard()). Sağlayıcı kartı tokenize eder ve sonucu
 *     PaymentResponse->cardUserKey + ->cardToken olarak döner.
 *  2. Sonradan PAN/CVC olmadan, non-3DS tahsilat: chargeStoredCard() bu
 *     token'larla çeker (recurring/abonelik için).
 */
interface CardStorageInterface
{
    public function chargeStoredCard(StoredCardChargeRequest $request): PaymentResponse;
}
