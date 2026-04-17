<?php

declare(strict_types=1);

namespace TruePos\Contracts;

use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\DataTransferObjects\ThreeDSecureData;

interface ThreeDSecureInterface
{
    /**
     * Build the form parameters for redirecting to the bank's 3DS page.
     */
    public function initializeThreeD(PaymentRequest $request): ThreeDSecureData;

    /**
     * Process the callback POST from the bank after 3DS authentication.
     * Verifies hash, then completes the payment if authenticated.
     */
    /**
     * @param  array<string, mixed>  $callbackData
     */
    public function completeThreeD(array $callbackData): PaymentResponse;

    /**
     * Validate the 3DS callback payload.
     *
     * For gateways with callback hash/signature (NestPay, Garanti, etc.):
     * performs cryptographic verification of the callback data.
     *
     * For gateways without callback signatures (PosNet, Iyzico):
     * performs payload sanity check (required fields present, token format).
     * Actual payment verification happens server-to-server during
     * completeThreeD() → provision API call.
     *
     * @param  array<string, mixed>  $callbackData
     */
    public function validateThreeDCallbackPayload(array $callbackData): bool;
}
