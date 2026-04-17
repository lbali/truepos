<?php

declare(strict_types=1);

namespace TruePos\DataTransferObjects;

use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\ValueObjects\Money;

final readonly class PaymentResponse
{
    public function __construct(
        public bool $isSuccessful,
        public TransactionStatus $status,
        public Gateway $gateway,
        public TransactionType $transactionType,
        public ?string $transactionId = null,
        public ?string $orderId = null,
        public ?string $authCode = null,
        public ?string $responseCode = null,
        public ?string $responseMessage = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public ?string $hostReferenceNumber = null,
        public ?Money $amount = null,
        public ?ThreeDSecureData $threeDSecureData = null,
        public ?string $mdStatus = null,
        public array $rawResponse = [],
        public \DateTimeImmutable $timestamp = new \DateTimeImmutable(),
    ) {}

    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }

    public function isThreeDRedirect(): bool
    {
        return $this->threeDSecureData !== null
            && $this->status === TransactionStatus::Pending;
    }

    public static function threeDRedirect(
        ThreeDSecureData $data,
        Gateway $gateway,
        ?string $orderId = null,
    ): self {
        return new self(
            isSuccessful: false,
            status: TransactionStatus::Pending,
            gateway: $gateway,
            transactionType: TransactionType::Purchase,
            orderId: $orderId,
            threeDSecureData: $data,
        );
    }

    public static function failed(
        Gateway $gateway,
        TransactionType $type,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        array $rawResponse = [],
    ): self {
        return new self(
            isSuccessful: false,
            status: TransactionStatus::Failed,
            gateway: $gateway,
            transactionType: $type,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse,
        );
    }
}
