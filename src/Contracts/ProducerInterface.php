<?php

declare(strict_types=1);

namespace Marwa\Kafka\Contracts;

use Marwa\Envelop\EnvelopBuilder;

interface ProducerInterface
{
    public function produce(string $topic, EnvelopBuilder $envelop, ?string $key = null, ?int $timestampMs = null, ?int $partition = null): void;

    public function flush(int $timeoutMs = 10000): void;
}
