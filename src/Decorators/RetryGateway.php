<?php

declare(strict_types=1);

namespace TruePos\Decorators;

use TruePos\Contracts\GatewayInterface;
use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Exceptions\GatewayException;
use TruePos\ValueObjects\Money;

final class RetryGateway implements GatewayInterface
{
    public function __construct(
        private readonly GatewayInterface $inner,
        private readonly int $maxRetries = 2,
        private readonly int $delayMs = 500,
    ) {}

    public function gateway(): Gateway
    {
        return $this->inner->gateway();
    }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        // Non-idempotent — do not retry to avoid duplicate charges
        return $this->inner->purchase($request);
    }

    public function preAuthorize(PaymentRequest $request): PaymentResponse
    {
        // Non-idempotent — do not retry to avoid duplicate holds
        return $this->inner->preAuthorize($request);
    }

    public function postAuthorize(string $transactionId, Money $amount): PaymentResponse
    {
        return $this->retry(fn () => $this->inner->postAuthorize($transactionId, $amount));
    }

    public function refund(RefundRequest $request): PaymentResponse
    {
        // Non-idempotent — do not retry to avoid duplicate refunds
        return $this->inner->refund($request);
    }

    public function cancel(CancelRequest $request): PaymentResponse
    {
        return $this->retry(fn () => $this->inner->cancel($request));
    }

    public function status(StatusRequest $request): PaymentResponse
    {
        return $this->retry(fn () => $this->inner->status($request));
    }

    public function supportsInstallment(): bool
    {
        return $this->inner->supportsInstallment();
    }

    public function supportedPaymentModels(): array
    {
        return $this->inner->supportedPaymentModels();
    }

    private function retry(\Closure $operation): PaymentResponse
    {
        $lastException = null;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $operation();
            } catch (GatewayException $e) {
                $lastException = $e;

                if ($attempt < $this->maxRetries) {
                    usleep($this->delayMs * 1000 * ($attempt + 1));
                }
            }
        }

        throw $lastException;
    }
}
