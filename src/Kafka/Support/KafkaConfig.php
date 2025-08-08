<?php

declare(strict_types=1);

namespace Marwa\Kafka\Kafka\Support;

final class KafkaConfig
{
    public function __construct(
        public readonly string $brokers,
        public readonly ?string $clientId = null,
        public readonly array $extra = []
    ) {}
}
