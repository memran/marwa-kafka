<?php

declare(strict_types=1);

namespace App\Kafka\Contracts;

use App\Kafka\DTO\Message;

interface ProducerInterface
{
    public function produce(Message $message): void;

    public function flush(int $timeoutMs = 10000): void;
}
