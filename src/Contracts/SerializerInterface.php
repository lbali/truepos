<?php

declare(strict_types=1);

namespace TruePos\Contracts;

interface SerializerInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function serialize(array $data): string;

    /**
     * @return array<string, mixed>
     */
    public function deserialize(string $payload): array;

    public function contentType(): string;
}
