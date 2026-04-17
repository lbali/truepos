<?php

declare(strict_types=1);

namespace TruePos\Contracts;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\ValueObjects\Money;

interface GatewayInterface
{
    public function gateway(): Gateway;

    public function purchase(PaymentRequest $request): PaymentResponse;

    public function preAuthorize(PaymentRequest $request): PaymentResponse;

    public function postAuthorize(string $transactionId, Money $amount): PaymentResponse;

    public function refund(RefundRequest $request): PaymentResponse;

    public function cancel(CancelRequest $request): PaymentResponse;

    public function status(StatusRequest $request): PaymentResponse;

    public function supportsInstallment(): bool;

    /**
     * @return PaymentModel[]
     */
    public function supportedPaymentModels(): array;
}
