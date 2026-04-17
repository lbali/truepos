<?php

declare(strict_types=1);

namespace TruePos\Models;

use Illuminate\Database\Eloquent\Model;
use TruePos\Enums\Currency;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

/**
 * @property int $id
 * @property string|null $order_id
 * @property string|null $transaction_id
 * @property Gateway $gateway
 * @property TransactionType $transaction_type
 * @property TransactionStatus $status
 * @property int $amount
 * @property string $currency
 * @property string|null $auth_code
 * @property string|null $response_code
 * @property string|null $response_message
 * @property string|null $error_code
 * @property string|null $error_message
 * @property string|null $host_reference_number
 * @property string|null $md_status
 * @property int $installment
 * @property array<string, mixed>|null $raw_response
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static static create(array<string, mixed> $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(string $column, mixed $operator = null, mixed $value = null)
 */
final class Transaction extends Model
{
    protected $table = 'truepos_transactions';

    protected $guarded = ['id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'gateway' => Gateway::class,
            'transaction_type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'amount' => 'integer',
            'installment' => 'integer',
            'raw_response' => 'array',
            'metadata' => 'array',
        ];
    }
}
