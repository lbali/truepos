<?php

declare(strict_types=1);

namespace TruePos\Gateways;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TruePos\Contracts\GatewayInterface;
use TruePos\Contracts\HashGeneratorInterface;
use TruePos\Contracts\ResponseParserInterface;
use TruePos\Contracts\SerializerInterface;
use TruePos\Contracts\ThreeDSecureInterface;
use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\DataTransferObjects\ThreeDSecureData;
use TruePos\Enums\TransactionType;
use TruePos\Exceptions\GatewayException;
use TruePos\Exceptions\HashMismatchException;
use TruePos\ValueObjects\Money;

abstract class AbstractGateway implements GatewayInterface, ThreeDSecureInterface
{
    /** @var (\Closure(string, string): void)|null */
    private ?\Closure $onThreeDInitialized = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected readonly array $config,
        protected readonly SerializerInterface $serializer,
        protected readonly HashGeneratorInterface $hashGenerator,
        protected readonly ResponseParserInterface $responseParser,
        protected readonly ClientInterface $httpClient,
        protected readonly ?RequestFactoryInterface $requestFactory = null,
        protected readonly ?StreamFactoryInterface $streamFactory = null,
    ) {}

    /**
     * Set a callback invoked when a 3DS redirect is created.
     * Receives (orderId, gatewayName) so the mapping can be stored.
     *
     * @param  (\Closure(string, string): void)|null  $callback
     */
    public function setOnThreeDInitialized(?\Closure $callback): void
    {
        $this->onThreeDInitialized = $callback;
    }

    // ─── Template Method: Purchase ───────────────────────────

    final public function purchase(PaymentRequest $request): PaymentResponse
    {
        if ($request->isThreeD()) {
            $threeDData = $this->initializeThreeD($request);

            if ($this->onThreeDInitialized !== null) {
                ($this->onThreeDInitialized)($request->orderId, $this->gateway()->value);
            }

            return PaymentResponse::threeDRedirect(
                data: $threeDData,
                gateway: $this->gateway(),
                orderId: $request->orderId,
            );
        }

        return $this->executeTransaction(
            parameters: $this->buildPurchaseParameters($request),
            type: TransactionType::Purchase,
        );
    }

    // ─── Template Method: Pre-Authorization ──────────────────

    final public function preAuthorize(PaymentRequest $request): PaymentResponse
    {
        if ($request->isThreeD()) {
            $threeDData = $this->initializeThreeD($request);

            if ($this->onThreeDInitialized !== null) {
                ($this->onThreeDInitialized)($request->orderId, $this->gateway()->value);
            }

            return PaymentResponse::threeDRedirect(
                data: $threeDData,
                gateway: $this->gateway(),
                orderId: $request->orderId,
            );
        }

        return $this->executeTransaction(
            parameters: $this->buildPreAuthParameters($request),
            type: TransactionType::PreAuth,
        );
    }

    // ─── Template Method: Post-Authorization (Capture) ───────

    final public function postAuthorize(string $transactionId, Money $amount): PaymentResponse
    {
        return $this->executeTransaction(
            parameters: $this->buildPostAuthParameters($transactionId, $amount),
            type: TransactionType::PostAuth,
        );
    }

    // ─── Template Method: Refund ─────────────────────────────

    final public function refund(RefundRequest $request): PaymentResponse
    {
        return $this->executeTransaction(
            parameters: $this->buildRefundParameters($request),
            type: TransactionType::Refund,
        );
    }

    // ─── Template Method: Cancel ─────────────────────────────

    final public function cancel(CancelRequest $request): PaymentResponse
    {
        return $this->executeTransaction(
            parameters: $this->buildCancelParameters($request),
            type: TransactionType::Cancel,
        );
    }

    // ─── Template Method: Status Query ───────────────────────

    final public function status(StatusRequest $request): PaymentResponse
    {
        return $this->executeTransaction(
            parameters: $this->buildStatusParameters($request),
            type: TransactionType::StatusQuery,
        );
    }

    // ─── 3D Secure: Initialize ───────────────────────────────

    final public function initializeThreeD(PaymentRequest $request): ThreeDSecureData
    {
        $params = $this->buildThreeDFormParameters($request);
        $hash = $this->hashGenerator->generate($params, $this->credentials());
        $params = $this->applyHash($params, $hash);

        return new ThreeDSecureData(
            gatewayUrl: $this->threeDGatewayUrl(),
            formParameters: $params,
        );
    }

    // ─── 3D Secure: Complete ─────────────────────────────────

    /** @param  array<string, mixed>  $callbackData */
    final public function completeThreeD(array $callbackData): PaymentResponse
    {
        if (! $this->validateThreeDCallbackPayload($callbackData)) {
            throw HashMismatchException::forCallback($this->gateway()->value);
        }

        $mdStatus = $this->extractMdStatus($callbackData);

        if (! $this->isThreeDAuthSuccessful($mdStatus)) {
            return PaymentResponse::failed(
                gateway: $this->gateway(),
                type: TransactionType::Purchase,
                errorCode: $mdStatus,
                errorMessage: '3D Secure authentication failed.',
                rawResponse: $callbackData,
            );
        }

        // 3D model: bank only authenticated, we must now charge via API.
        // 3D Pay model: bank already charged, just parse the callback.
        if ($this->requiresProvisionAfterThreeD()) {
            return $this->executeTransaction(
                parameters: $this->buildThreeDProvisionParameters($callbackData),
                type: TransactionType::Purchase,
            );
        }

        return $this->responseParser->parseThreeDCallback($callbackData);
    }

    // ─── Core execution engine ───────────────────────────────

    /** @param  array<string, mixed>  $parameters */
    private function executeTransaction(array $parameters, TransactionType $type): PaymentResponse
    {
        try {
            $hash = $this->hashGenerator->generate($parameters, $this->credentials());
            $parameters = $this->applyHash($parameters, $hash);

            $headers = $this->buildHttpHeaders($parameters);

            // Remove internal underscore-prefixed keys before serialization
            $parameters = array_filter($parameters, static fn ($key): bool => !str_starts_with((string) $key, '_'), ARRAY_FILTER_USE_KEY);

            $payload = $this->serializer->serialize($parameters);

            $rawResponse = $this->sendRequest($payload, $this->endpointFor($type), $headers);
            $parsed = $this->serializer->deserialize($rawResponse);

            return $this->responseParser->parse($parsed, $type);
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw GatewayException::connectionFailed($this->gateway()->value, $e);
        }
    }

    /**
     * Build additional HTTP headers for the request.
     * Override in gateways that require custom headers (e.g. Craftgate signature headers).
     *
     * @param  array<string, mixed>  $parameters  The full parameter set (including _ prefixed internal keys)
     * @return array<string, string>
     */
    protected function buildHttpHeaders(array $parameters): array
    {
        return [];
    }

    /** @param  array<string, string>  $headers */
    private function sendRequest(string $payload, string $url, array $headers = []): string
    {
        $allHeaders = array_merge(['Content-Type' => $this->serializer->contentType()], $headers);

        if ($this->requestFactory !== null && $this->streamFactory !== null) {
            $request = $this->requestFactory->createRequest('POST', $url);
            foreach ($allHeaders as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
            $request = $request->withBody($this->streamFactory->createStream($payload));
        } else {
            $request = new \GuzzleHttp\Psr7\Request(
                'POST',
                $url,
                $allHeaders,
                $payload,
            );
        }

        $response = $this->httpClient->sendRequest($request);

        return (string) $response->getBody();
    }

    // ─── Endpoint resolution ─────────────────────────────────

    /**
     * Override in gateways with different URLs per transaction type
     * (e.g., REST APIs: Lidio /api/Refund, /api/Cancel, etc.).
     * Default: returns endpoint() for all types.
     */
    protected function endpointFor(TransactionType $type): string
    {
        return $this->endpoint();
    }

    // ─── Abstract hooks — each gateway must implement ────────

    /** @return array<string, mixed> */
    abstract protected function buildPurchaseParameters(PaymentRequest $request): array;

    /** @return array<string, mixed> */
    abstract protected function buildPreAuthParameters(PaymentRequest $request): array;

    /** @return array<string, mixed> */
    abstract protected function buildPostAuthParameters(string $transactionId, Money $amount): array;

    /** @return array<string, mixed> */
    abstract protected function buildRefundParameters(RefundRequest $request): array;

    /** @return array<string, mixed> */
    abstract protected function buildCancelParameters(CancelRequest $request): array;

    /** @return array<string, mixed> */
    abstract protected function buildStatusParameters(StatusRequest $request): array;

    /** @return array<string, mixed> */
    abstract protected function buildThreeDFormParameters(PaymentRequest $request): array;

    /**
     * @param  array<string, mixed>  $callbackData
     * @return array<string, mixed>
     */
    abstract protected function buildThreeDProvisionParameters(array $callbackData): array;

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    abstract protected function applyHash(array $parameters, string $hash): array;

    /** @return array<string, mixed> */
    abstract protected function credentials(): array;

    abstract protected function endpoint(): string;

    abstract protected function threeDGatewayUrl(): string;

    /** @param  array<string, mixed>  $callbackData */
    abstract protected function extractMdStatus(array $callbackData): ?string;

    abstract protected function isThreeDAuthSuccessful(?string $mdStatus): bool;

    /**
     * Whether the gateway requires a second API call after 3DS auth.
     * True for 3D model (NestPay, Garanti). False for 3D Pay model.
     */
    abstract protected function requiresProvisionAfterThreeD(): bool;
}
