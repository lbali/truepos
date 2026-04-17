<?php

declare(strict_types=1);

namespace TruePos\Contracts;

interface SerializerInterface
{
    public function serialize(array $data): string;

    public function deserialize(string $payload): array;

    public function contentType(): string;
}
