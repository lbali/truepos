<?php

declare(strict_types=1);

namespace TruePos\Decorators;

use Psr\Log\LoggerInterface;
use TruePos\Contracts\GatewayInterface;
use TruePos\Contracts\ThreeDSecureInterface;
use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\DataTransferObjects\ThreeDSecureData;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\ValueObjects\Money;

final class LoggingGateway implements GatewayInterface, ThreeDSecureInterface
{
    public function __construct(
        private readonly GatewayInterface $inner,
        private readonly LoggerInterface $logger,
    ) {}

    public function gateway(): Gateway
    {
        return $this->inner->gateway();
    }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        return $this->logged('purchase', $request->orderId, $request->amount, fn () => $this->inner->purchase($request));
    }

    public function preAuthorize(PaymentRequest $request): PaymentResponse
    {
        return $this->logged('preAuthorize', $request->orderId, $request->amount, fn () => $this->inner->preAuthorize($request));
    }

    public function postAuthorize(string $transactionId, Money $amount): PaymentResponse
    {
        return $this->logged('postAuthorize', $transactionId, $amount, fn () => $this->inner->postAuthorize($transactionId, $amount));
    }

    public function refund(RefundRequest $request): PaymentResponse
    {
        return $this->logged('refund', $request->orderId, $request->amount, fn () => $this->inner->refund($request));
    }

    public function cancel(CancelRequest $request): PaymentResponse
    {
        return $this->logged('cancel', $request->orderId, null, fn () => $this->inner->cancel($request));
    }

    public function status(StatusRequest $request): PaymentResponse
    {
        return $this->logged('status', $request->orderId, null, fn () => $this->inner->status($request));
    }

    public function supportsInstallment(): bool
    {
        return $this->inner->supportsInstallment();
    }

    public function supportedPaymentModels(): array
    {
        return $this->inner->supportedPaymentModels();
    }

    public function initializeThreeD(PaymentRequest $request): ThreeDSecureData
    {
        if ($this->inner instanceof ThreeDSecureInterface) {
            $this->logger->info('TruePos: Initializing 3D Secure', [
                'gateway' => $this->inner->gateway()->value,
                'orderId' => $request->orderId,
            ]);

            return $this->inner->initializeThreeD($request);
        }

        throw new \BadMethodCallException('Inner gateway does not support 3D Secure.');
    }

    public function completeThreeD(array $callbackData): PaymentResponse
    {
        if ($this->inner instanceof ThreeDSecureInterface) {
            return $this->inner->completeThreeD($callbackData);
        }

        throw new \BadMethodCallException('Inner gateway does not support 3D Secure.');
    }

    public function validateThreeDCallbackPayload(array $callbackData): bool
    {
        if ($this->inner instanceof ThreeDSecureInterface) {
            return $this->inner->validateThreeDCallbackPayload($callbackData);
        }

        throw new \BadMethodCallException('Inner gateway does not support 3D Secure.');
    }

    /**
     * @param  \Closure(): PaymentResponse  $operation
     */
    private function logged(string $method, string $identifier, ?Money $amount, \Closure $operation): PaymentResponse
    {
        $context = [
            'gateway' => $this->inner->gateway()->value,
            'method' => $method,
            'identifier' => $identifier,
        ];

        if ($amount !== null) {
            $context['amount'] = $amount->toDecimal();
            $context['currency'] = $amount->currency->name;
        }

        $this->logger->info("TruePos: {$method} started", $context);

        try {
            $response = $operation();

            $this->logger->info("TruePos: {$method} completed", [
                ...$context,
                'success' => $response->isSuccessful(),
                'responseCode' => $response->responseCode,
                'transactionId' => $response->transactionId,
            ]);

            return $response;
        } catch (\Throwable $e) {
            $this->logger->error("TruePos: {$method} failed", [
                ...$context,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
