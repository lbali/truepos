<?php

declare(strict_types=1);

namespace TruePos\DataTransferObjects;

final readonly class ThreeDSecureData
{
    /**
     * İki 3DS modelini taşır:
     *  - Form-POST (NestPay, Garanti...): gatewayUrl + formParameters; tarayıcı POST'lar.
     *  - HTML-içerik (iyzico): htmlContent doğrudan render edilir (server-to-server initialize).
     *
     * @param  string  $gatewayUrl  Bankanın 3DS gateway URL'i (form-POST modeli).
     * @param  array<string, string>  $formParameters  3DS yönlendirmesi için gizli form alanları.
     * @param  string  $method  HTTP metodu (Türk POS'larında daima POST).
     * @param  string|null  $htmlContent  Hazır 3DS HTML'i (server-init modeli; iyzico).
     */
    public function __construct(
        public string $gatewayUrl = '',
        public array $formParameters = [],
        public string $method = 'POST',
        public ?string $htmlContent = null,
    ) {}

    public function hasHtmlContent(): bool
    {
        return $this->htmlContent !== null && $this->htmlContent !== '';
    }
}
