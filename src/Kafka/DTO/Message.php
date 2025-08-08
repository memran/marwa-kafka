<?php

declare(strict_types=1);

namespace App\Kafka\DTO;

final class Message
{
    public function __construct(
        public readonly string $topic,
        public readonly mixed $payload,
        public readonly ?string $key = null,
        public readonly array $headers = [],
        public readonly ?int $partition = null,
        public readonly ?int $timestampMs = null
    ) {}
}
