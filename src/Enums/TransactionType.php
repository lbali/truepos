<?php

declare(strict_types=1);

namespace TruePos\Enums;

enum TransactionType: string
{
    case Purchase = 'purchase';
    case PreAuth = 'pre_auth';
    case PostAuth = 'post_auth';
    case Refund = 'refund';
    case Cancel = 'cancel';
    case StatusQuery = 'status';
}
