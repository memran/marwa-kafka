<?php

declare(strict_types=1);

namespace Marwa\Kafka\Contracts;

use Marwa\Envelop\Envelop;

interface ProducerInterface
{
    public function withHost(string $host): self;

    public function withTopics(array $topics): self;

    public function produce(
        string $topic,
        Envelop $envelop,
        ?string $key = null,
        ?int $timestampMs = null,
        ?int $partition = null
    ): void;

    public function flush(int $timeoutMs = 10000): void;
}
