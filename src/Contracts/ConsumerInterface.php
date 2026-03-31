<?php

declare(strict_types=1);

namespace Marwa\Kafka\Contracts;

use Psr\Log\LoggerInterface;

interface ConsumerInterface
{
    public function withHost(string $host): self;

    public function withLogger(LoggerInterface $logger): self;

    public function withErrorHandler(callable $errorHandler): self;

    /**
     * @param list<string> $topics
     */
    public function withTopics(array $topics): self;

    public function run(callable $onMessage, int $pollTimeoutMs = 1000): void;

    public function runOnce(callable $onMessage, int $pollTimeoutMs = 500): bool;

    public function stop(): void;
}
