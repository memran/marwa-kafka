<?php

declare(strict_types=1);

namespace Marwa\Kafka\Contracts;

interface ConsumerInterface
{
    public function withHost(string $host): self;

    /**
     * @param list<string> $topics
     */
    public function withTopics(array $topics): self;

    public function run(callable $onMessage, int $pollTimeoutMs = 1000): void;

    public function runOnce(callable $onMessage, int $pollTimeoutMs = 500): bool;

    public function stop(): void;
}
