<?php

declare(strict_types=1);

namespace Marwa\Kafka\Tests\Producer;

use Marwa\Kafka\Producer\KafkaProducer;
use Marwa\Kafka\Support\KafkaConfig;
use PHPUnit\Framework\TestCase;

final class KafkaProducerValidationTest extends TestCase
{
    public function testItRejectsBlankHostOverride(): void
    {
        $producer = new KafkaProducer(new KafkaConfig(['brokers' => 'kafka:9092']));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka host must be a non-empty string.');

        $producer->withHost(' ');
    }

    public function testItRejectsInvalidFlushTimeoutBeforeTalkingToKafka(): void
    {
        $producer = new KafkaProducer(new KafkaConfig(['brokers' => 'kafka:9092']));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Flush timeout must be greater than zero milliseconds.');

        $producer->flush(0);
    }
}
