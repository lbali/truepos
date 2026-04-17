<?php

declare(strict_types=1);

namespace TruePos\Contracts;

interface HashGeneratorInterface
{
    /**
     * Generate a hash/signature for the given parameters using gateway credentials.
     *
     * Each gateway has a completely different hashing algorithm:
     * - NestPay: SHA-512 of pipe-delimited fields
     * - Garanti: SHA-512 of SHA-512 of specific field concatenation
     * - PosNet: MAC using specific key derivation
     */
    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $credentials
     */
    public function generate(array $parameters, array $credentials): string;

    /**
     * Verify a hash received from the gateway callback.
     */
    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $credentials
     */
    public function verify(string $expected, array $parameters, array $credentials): bool;
}
