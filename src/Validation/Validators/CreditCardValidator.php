<?php

declare(strict_types=1);

namespace TruePos\Validation\Validators;

use TruePos\Enums\PaymentModel;
use TruePos\Validation\ValidatorInterface;
use TruePos\ValueObjects\CreditCard;

final class CreditCardValidator implements ValidatorInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function validate(array $data): array
    {
        $errors = [];
        $card = $data['card'] ?? null;
        $paymentModel = $data['paymentModel'] ?? PaymentModel::Regular;

        // 3D Host doesn't require a card — the bank collects card info.
        if ($paymentModel === PaymentModel::ThreeDHost) {
            return $errors;
        }

        if (! $card instanceof CreditCard) {
            $errors['card'][] = 'Credit card is required for this payment model.';
        }

        return $errors;
    }
}
