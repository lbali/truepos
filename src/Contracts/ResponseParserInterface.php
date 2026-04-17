<?php

declare(strict_types=1);

namespace TruePos\Contracts;

use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Enums\TransactionType;

interface ResponseParserInterface
{
    /**
     * Normalize the raw gateway response into a unified PaymentResponse.
     * This is where the Adapter pattern lives: each parser maps
     * gateway-specific fields into our unified DTO.
     */
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function parse(array $rawResponse, TransactionType $type): PaymentResponse;

    /**
     * Parse a 3DS callback response (different structure from API response).
     */
    /**
     * @param  array<string, mixed>  $callbackData
     */
    public function parseThreeDCallback(array $callbackData): PaymentResponse;
}
