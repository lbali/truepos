<?php

declare(strict_types=1);

namespace TruePos\DataTransferObjects;

use TruePos\ValueObjects\Customer;
use TruePos\ValueObjects\Money;

/**
 * Saklı kartla (token) tek çekim isteği — PAN/CVC yok, non-3DS. cardUserKey +
 * cardToken, daha önce PaymentRequest::$storeCard ile tokenize edilmiş bir
 * karttan (PaymentResponse) gelir. Recurring/abonelik yenilemeleri için kullanılır.
 */
final readonly class StoredCardChargeRequest
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public Money $amount,
        public string $orderId,
        public string $cardUserKey,
        public string $cardToken,
        public int $installment = 0,
        public ?Customer $customer = null,
        public array $metadata = [],
    ) {}

    public function hasInstallment(): bool
    {
        return $this->installment > 1;
    }
}
