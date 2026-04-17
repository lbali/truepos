<?php

declare(strict_types=1);

namespace TruePos\DataTransferObjects;

final readonly class ThreeDSecureData
{
    /**
     * @param  string  $gatewayUrl  The bank's 3DS gateway URL to POST the form to.
     * @param  array<string, string>  $formParameters  Hidden form fields for the 3DS redirect.
     * @param  string  $method  HTTP method (always POST for Turkish POS).
     */
    public function __construct(
        public string $gatewayUrl,
        public array $formParameters,
        public string $method = 'POST',
    ) {}
}
