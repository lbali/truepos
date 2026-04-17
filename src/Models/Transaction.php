<?php

declare(strict_types=1);

namespace TruePos\Models;

use Illuminate\Database\Eloquent\Model;
use TruePos\Enums\Currency;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;

final class Transaction extends Model
{
    protected $table = 'truepos_transactions';

    protected $guarded = ['id'];

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
