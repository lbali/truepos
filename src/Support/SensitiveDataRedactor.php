<?php

declare(strict_types=1);

namespace TruePos\Support;

final class SensitiveDataRedactor
{
    private const SENSITIVE_KEYS = [
        'cardnumber', 'cardno', 'card_number', 'cc_number', 'pan', 'number',
        'cvv', 'cvv2', 'cvc', 'cc_cvv', 'cvc2val', 'cvv2val',
        'expiredate', 'expdate', 'exp_month', 'exp_year', 'expiry',
        'cardexpiredatemonth', 'cardexpiredateyear',
        'password', 'merchantpassword', 'merchant_pass', 'apipass', 'api_pass',
        'secret', 'secretkey', 'secret_key', 'store_key', 'storekey',
        'merchant_key', 'merchantkey', 'enc_key',
        'hash', 'hashdata', 'hashstr', 'auth_hash', 'islem_hash',
        'secure3dhash', 'hashvalue', 'hash_key',
        'md', 'cavv', 'eci', 'xid',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function redact(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            if (in_array($lowerKey, self::SENSITIVE_KEYS, true)) {
                $result[$key] = is_string($value) && strlen($value) > 4
                    ? substr($value, 0, 2) . str_repeat('*', strlen($value) - 4) . substr($value, -2)
                    : '***';
            } elseif (is_array($value)) {
                $result[$key] = self::redact($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
