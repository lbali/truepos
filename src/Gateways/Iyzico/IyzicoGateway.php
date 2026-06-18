<?php

declare(strict_types=1);

namespace TruePos\Gateways\Iyzico;

use TruePos\Contracts\CardStorageInterface;
use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\DataTransferObjects\StoredCardChargeRequest;
use TruePos\DataTransferObjects\ThreeDSecureData;
use TruePos\Enums\Currency;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Enums\TransactionType;
use TruePos\Exceptions\ThreeDSecureException;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * iyzico payment facilitator gateway.
 *
 * REST JSON API with PKI-based authorization headers.
 * Amount is decimal string (e.g., "1.0", "100.50").
 * Requires basket items in every payment request.
 * 3DS uses iyzico's initialize/callback flow.
 */
final class IyzicoGateway extends AbstractGateway implements CardStorageInterface
{
    public function gateway(): Gateway
    {
        return Gateway::Iyzico;
    }

    public function supportsInstallment(): bool
    {
        return true;
    }

    public function supportedPaymentModels(): array
    {
        return [
            PaymentModel::Regular,
            PaymentModel::ThreeD,
        ];
    }

    /**
     * Saklı kartla (cardUserKey + cardToken) tek çekim — PAN/CVC yok, non-3DS.
     * Recurring/abonelik yenilemeleri için (CardStorageInterface).
     */
    public function chargeStoredCard(StoredCardChargeRequest $request): PaymentResponse
    {
        return $this->executeTransaction(
            $this->buildStoredCardParameters($request),
            TransactionType::Purchase,
        );
    }

    /**
     * iyzico 3DS callback doğrulaması (kriptografik değil). iyzico /payment/3dsecure/auth
     * callback'i paymentId + conversationId döndürür; gerçek doğrulama completeThreeD()
     * içindeki server-to-server provision çağrısında olur (iyzico geçersiz paymentId'yi
     * orada reddeder). Bu metot yalnız zorunlu alanların varlığını kontrol eder.
     *
     * @param  array<string, mixed>  $callbackData
     */
    public function validateThreeDCallbackPayload(array $callbackData): bool
    {
        return ! empty($callbackData['paymentId']) && ! empty($callbackData['conversationId']);
    }

    protected function buildPurchaseParameters(PaymentRequest $request): array
    {
        return [
            'locale' => $this->config['locale'] ?? 'tr',
            'conversationId' => $request->orderId,
            'price' => $request->amount->toDecimal(),
            'paidPrice' => $request->amount->toDecimal(),
            'currency' => $this->currencyCode($request->amount),
            'installment' => $request->hasInstallment() ? $request->installment : 1,
            'basketId' => $request->orderId,
            'paymentChannel' => 'WEB',
            'paymentGroup' => 'PRODUCT',
            'paymentCard' => [
                'cardHolderName' => $request->card?->holderName ?? '',
                'cardNumber' => $request->card?->number ?? '',
                'expireMonth' => $request->card ? str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT) : '',
                'expireYear' => $request->card ? (strlen($request->card->expiryYear) === 2 ? '20'.$request->card->expiryYear : $request->card->expiryYear) : '',
                'cvc' => $request->card?->cvv ?? '',
                'registerCard' => $request->storeCard ? '1' : '0',
            ],
            'buyer' => [
                'id' => $request->customer?->identity ?? 'BUYER_'.$request->orderId,
                'name' => $this->extractFirstName($request->customer?->name),
                'surname' => $this->extractLastName($request->customer?->name),
                'email' => $request->customer?->email ?? 'noemail@example.com',
                'ip' => $request->customer?->ip ?? '',
                'identityNumber' => $request->customer?->identity ?? '11111111111',
                'registrationAddress' => 'N/A',
                'city' => 'Istanbul',
                'country' => 'Turkey',
            ],
            'shippingAddress' => [
                'contactName' => $request->customer?->name ?? 'N/A',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => 'N/A',
            ],
            'billingAddress' => [
                'contactName' => $request->customer?->name ?? 'N/A',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => 'N/A',
            ],
            'basketItems' => [
                [
                    'id' => $request->orderId,
                    'name' => $request->metadata['itemName'] ?? 'Ödeme',
                    'category1' => $request->metadata['category'] ?? 'Default',
                    'itemType' => 'PHYSICAL',
                    'price' => $request->amount->toDecimal(),
                ],
            ],
        ];
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['paymentGroup'] = 'PRODUCT';

        return $params;
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'locale' => $this->config['locale'] ?? 'tr',
            'paymentTransactionId' => $transactionId,
            'paidPrice' => $amount->toDecimal(),
            'currency' => $this->currencyCode($amount),
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'locale' => $this->config['locale'] ?? 'tr',
            'conversationId' => $request->orderId,
            'paymentTransactionId' => $request->transactionId ?? '',
            'price' => $request->amount->toDecimal(),
            'currency' => $this->currencyCode($request->amount),
            'ip' => '127.0.0.1',
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'locale' => $this->config['locale'] ?? 'tr',
            'conversationId' => $request->orderId,
            'paymentId' => $request->transactionId ?? '',
            'ip' => '127.0.0.1',
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'locale' => $this->config['locale'] ?? 'tr',
            'conversationId' => $request->orderId,
            'paymentId' => $request->transactionId ?? '',
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['callbackUrl'] = $request->callbackUrl ?? $this->config['callback_url'] ?? '';

        return $params;
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        $params = [
            'locale' => $this->config['locale'] ?? 'tr',
            'conversationId' => $callbackData['conversationId'] ?? '',
            'paymentId' => $callbackData['paymentId'] ?? '',
        ];

        if (! empty($callbackData['conversationData'])) {
            $params['conversationData'] = $callbackData['conversationData'];
        }

        return $params;
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        $parameters['_authorization'] = $hash;
        $parameters['_random'] = $parameters['_random'] ?? bin2hex(random_bytes(8));

        return $parameters;
    }

    /**
     * iyzico v2 kimlik doğrulama (IYZWSv2). İmza gövdeye ve URL path'ine bağlı olduğundan
     * post-serialization signRequest hook'unda üretilir:
     *   signature = hex( HMAC-SHA256(randomKey + uriPath + body, secretKey) )
     *   Authorization: IYZWSv2 base64("apiKey:..&randomKey:..&signature:..")
     *   x-iyzi-rnd: randomKey
     * (Legacy v1 IYZWS/PKI yalnız 3DS form yolunda kalır; v2 server-to-server'da.)
     *
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    protected function signRequest(string $payload, string $url, array $headers): array
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        $secretKey = (string) ($this->config['secret_key'] ?? '');
        $randomKey = time().bin2hex(random_bytes(8));
        $uriPath = (string) (parse_url($url, PHP_URL_PATH) ?: '');
        $signature = hash_hmac('sha256', $randomKey.$uriPath.$payload, $secretKey);

        $authParams = 'apiKey:'.$apiKey.'&randomKey:'.$randomKey.'&signature:'.$signature;
        $headers['Authorization'] = 'IYZWSv2 '.base64_encode($authParams);
        $headers['x-iyzi-rnd'] = $randomKey;
        $headers['Accept'] = 'application/json';

        return $headers;
    }

    protected function credentials(): array
    {
        return [
            'apiKey' => $this->config['api_key'],
            'secretKey' => $this->config['secret_key'],
        ];
    }

    protected function endpoint(): string
    {
        return $this->config['payment_url'];
    }

    protected function threeDGatewayUrl(): string
    {
        return $this->config['threed_gateway_url'];
    }

    protected function extractMdStatus(array $callbackData): ?string
    {
        return $callbackData['status'] ?? null;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        return $mdStatus === 'success';
    }

    protected function requiresProvisionAfterThreeD(): bool
    {
        return true;
    }

    protected function threeDUsesServerInitialize(): bool
    {
        return true;
    }

    /**
     * iyzico /payment/3dsecure/initialize yanıtı: { status, threeDSHtmlContent (base64) }.
     * Hazır HTML'i çözüp ThreeDSecureData->htmlContent olarak döner.
     *
     * @param  array<string, mixed>  $parsed
     */
    protected function parseThreeDInitialize(array $parsed): ThreeDSecureData
    {
        if (($parsed['status'] ?? '') !== 'success') {
            throw ThreeDSecureException::initializationFailed($parsed['errorMessage'] ?? null);
        }

        $html = (string) ($parsed['threeDSHtmlContent'] ?? '');
        $decoded = base64_decode($html, true);

        return new ThreeDSecureData(htmlContent: $decoded !== false && $decoded !== '' ? $decoded : $html);
    }

    /** 3DS tamamlama iyzico'da /payment/3dsecure/auth'a gider (purchase endpoint'i değil). */
    protected function threeDProvisionEndpoint(): ?string
    {
        if (! empty($this->config['threed_auth_url'])) {
            return (string) $this->config['threed_auth_url'];
        }

        $parts = parse_url((string) ($this->config['payment_url'] ?? ''));
        if (isset($parts['scheme'], $parts['host'])) {
            return $parts['scheme'].'://'.$parts['host'].'/payment/3dsecure/auth';
        }

        return null;
    }

    /**
     * Saklı kart parametreleri — buildPurchaseParameters ile aynı, ancak paymentCard
     * ham kart yerine cardUserKey + cardToken taşır.
     *
     * @return array<string, mixed>
     */
    private function buildStoredCardParameters(StoredCardChargeRequest $request): array
    {
        return [
            'locale' => $this->config['locale'] ?? 'tr',
            'conversationId' => $request->orderId,
            'price' => $request->amount->toDecimal(),
            'paidPrice' => $request->amount->toDecimal(),
            'currency' => $this->currencyCode($request->amount),
            'installment' => $request->hasInstallment() ? $request->installment : 1,
            'basketId' => $request->orderId,
            'paymentChannel' => 'WEB',
            'paymentGroup' => 'PRODUCT',
            'paymentCard' => [
                'cardUserKey' => $request->cardUserKey,
                'cardToken' => $request->cardToken,
            ],
            'buyer' => [
                'id' => $request->customer?->identity ?? 'BUYER_'.$request->orderId,
                'name' => $this->extractFirstName($request->customer?->name),
                'surname' => $this->extractLastName($request->customer?->name),
                'email' => $request->customer?->email ?? 'noemail@example.com',
                'ip' => $request->customer?->ip ?? '',
                'identityNumber' => $request->customer?->identity ?? '11111111111',
                'registrationAddress' => 'N/A',
                'city' => 'Istanbul',
                'country' => 'Turkey',
            ],
            'shippingAddress' => [
                'contactName' => $request->customer?->name ?? 'N/A',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => 'N/A',
            ],
            'billingAddress' => [
                'contactName' => $request->customer?->name ?? 'N/A',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => 'N/A',
            ],
            'basketItems' => [
                [
                    'id' => $request->orderId,
                    'name' => $request->metadata['itemName'] ?? 'Ödeme',
                    'category1' => $request->metadata['category'] ?? 'Default',
                    'itemType' => 'PHYSICAL',
                    'price' => $request->amount->toDecimal(),
                ],
            ],
        ];
    }

    private function currencyCode(Money $money): string
    {
        return match ($money->currency) {
            Currency::TRY => 'TRY',
            Currency::USD => 'USD',
            Currency::EUR => 'EUR',
            Currency::GBP => 'GBP',
        };
    }

    private function extractFirstName(?string $fullName): string
    {
        if ($fullName === null || $fullName === '') {
            return 'N/A';
        }

        $parts = explode(' ', trim($fullName));

        return $parts[0];
    }

    private function extractLastName(?string $fullName): string
    {
        if ($fullName === null || $fullName === '') {
            return 'N/A';
        }

        $parts = explode(' ', trim($fullName));

        return count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : $parts[0];
    }
}
