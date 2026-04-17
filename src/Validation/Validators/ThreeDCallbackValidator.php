<?php

declare(strict_types=1);

namespace TruePos\Validation\Validators;

use TruePos\Enums\PaymentModel;
use TruePos\Validation\ValidatorInterface;

final class ThreeDCallbackValidator implements ValidatorInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function validate(array $data): array
    {
        $errors = [];
        $paymentModel = $data['paymentModel'] ?? PaymentModel::Regular;
        $callbackUrl = $data['callbackUrl'] ?? null;

        if ($paymentModel->isThreeD() && empty($callbackUrl)) {
            $errors['callbackUrl'][] = 'Callback URL is required for 3D Secure payments.';
        }

        return $errors;
    }
}
