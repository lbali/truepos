<?php

declare(strict_types=1);

namespace TruePos\Exceptions;

use TruePos\DataTransferObjects\PaymentResponse;

class PaymentDeclinedException extends TruePosException
{
    public function __construct(
        public readonly PaymentResponse $response,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $response->errorMessage ?? 'Payment declined.',
            gatewayErrorCode: $response->errorCode,
            previous: $previous,
        );
    }
}
