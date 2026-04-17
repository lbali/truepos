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
    public function completeThreeD(array $callbackData): PaymentResponse;

    /**
     * Verify the hash/signature in the 3DS callback data.
     */
    public function verifyThreeDCallback(array $callbackData): bool;
}
