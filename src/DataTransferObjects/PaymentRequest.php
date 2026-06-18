<?php

declare(strict_types=1);

namespace TruePos\DataTransferObjects;

use TruePos\Enums\PaymentModel;
use TruePos\Enums\TransactionType;
use TruePos\ValueObjects\CreditCard;
use TruePos\ValueObjects\Customer;
use TruePos\ValueObjects\Money;

final readonly class PaymentRequest
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public Money $amount,
        public string $orderId,
        public TransactionType $type = TransactionType::Purchase,
        public PaymentModel $paymentModel = PaymentModel::Regular,
        public ?CreditCard $card = null,
        public int $installment = 0,
        public ?Customer $customer = null,
        public ?string $callbackUrl = null,
        public array $metadata = [],
        /** true ise (destekleyen gateway'de) kart tokenize edilir; token PaymentResponse'ta döner. */
        public bool $storeCard = false,
    ) {}

    public function isThreeD(): bool
    {
        return $this->paymentModel->isThreeD();
    }

    public function hasInstallment(): bool
    {
        return $this->installment > 1;
    }
}
