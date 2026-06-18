<?php

declare(strict_types=1);

namespace TruePos\Builder;

use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\Enums\Currency;
use TruePos\Enums\PaymentModel;
use TruePos\Enums\TransactionType;
use TruePos\Exceptions\ValidationException;
use TruePos\Validation\ValidationPipeline;
use TruePos\ValueObjects\CreditCard;
use TruePos\ValueObjects\Customer;
use TruePos\ValueObjects\Money;

final class PaymentRequestBuilder
{
    private ?CreditCard $card = null;

    private ?Money $amount = null;

    private ?string $orderId = null;

    private PaymentModel $paymentModel = PaymentModel::Regular;

    private TransactionType $type = TransactionType::Purchase;

    private int $installment = 0;

    private ?Customer $customer = null;

    private ?string $callbackUrl = null;

    /** @var array<string, mixed> */
    private array $metadata = [];

    private bool $storeCard = false;

    private function __construct() {}

    public static function create(): self
    {
        return new self;
    }

    public function card(CreditCard $card): self
    {
        $clone = clone $this;
        $clone->card = $card;

        return $clone;
    }

    public function amount(Money|float|int $amount, Currency $currency = Currency::TRY): self
    {
        $clone = clone $this;
        $clone->amount = $amount instanceof Money
            ? $amount
            : Money::fromDecimal((float) $amount, $currency);

        return $clone;
    }

    public function orderId(string $orderId): self
    {
        $clone = clone $this;
        $clone->orderId = $orderId;

        return $clone;
    }

    public function installment(int $months): self
    {
        $clone = clone $this;
        $clone->installment = $months;

        return $clone;
    }

    public function threeD(?string $callbackUrl = null): self
    {
        $clone = clone $this;
        $clone->paymentModel = PaymentModel::ThreeD;
        $clone->callbackUrl = $callbackUrl;

        return $clone;
    }

    public function threeDPay(?string $callbackUrl = null): self
    {
        $clone = clone $this;
        $clone->paymentModel = PaymentModel::ThreeDPay;
        $clone->callbackUrl = $callbackUrl;

        return $clone;
    }

    public function threeDHost(?string $callbackUrl = null): self
    {
        $clone = clone $this;
        $clone->paymentModel = PaymentModel::ThreeDHost;
        $clone->callbackUrl = $callbackUrl;

        return $clone;
    }

    public function regular(): self
    {
        $clone = clone $this;
        $clone->paymentModel = PaymentModel::Regular;

        return $clone;
    }

    public function preAuth(): self
    {
        $clone = clone $this;
        $clone->type = TransactionType::PreAuth;

        return $clone;
    }

    public function customer(Customer $customer): self
    {
        $clone = clone $this;
        $clone->customer = $customer;

        return $clone;
    }

    public function callbackUrl(string $url): self
    {
        $clone = clone $this;
        $clone->callbackUrl = $url;

        return $clone;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function metadata(array $data): self
    {
        $clone = clone $this;
        $clone->metadata = $data;

        return $clone;
    }

    /** Kartı tokenize et (destekleyen gateway'de); token PaymentResponse'ta döner. */
    public function storeCard(bool $store = true): self
    {
        $clone = clone $this;
        $clone->storeCard = $store;

        return $clone;
    }

    public function build(): PaymentRequest
    {
        $orderId = $this->orderId ?? self::generateOrderId();

        ValidationPipeline::default()->validate([
            'card' => $this->card,
            'amount' => $this->amount,
            'installment' => $this->installment,
            'paymentModel' => $this->paymentModel,
            'callbackUrl' => $this->callbackUrl,
        ]);

        if ($this->amount === null) {
            throw ValidationException::withErrors(['amount' => ['Amount is required.']]);
        }

        return new PaymentRequest(
            amount: $this->amount,
            orderId: $orderId,
            type: $this->type,
            paymentModel: $this->paymentModel,
            card: $this->card,
            installment: $this->installment,
            customer: $this->customer,
            callbackUrl: $this->callbackUrl,
            metadata: $this->metadata,
            storeCard: $this->storeCard,
        );
    }

    private static function generateOrderId(): string
    {
        return 'TP'.date('Ymd').strtoupper(bin2hex(random_bytes(6)));
    }
}
