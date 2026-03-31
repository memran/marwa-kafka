<?php

declare(strict_types=1);

namespace Marwa\Kafka\Tests\Consumer;

use Marwa\Kafka\Consumer\KafkaConsumer;
use Marwa\Kafka\Support\KafkaConfig;
use PHPUnit\Framework\TestCase;

final class KafkaConsumerValidationTest extends TestCase
{
    public function testItRejectsBlankGroupId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka consumer groupId must be a non-empty string.');

        new KafkaConsumer(new KafkaConfig(['brokers' => 'kafka:9092']), ' ', 'secret');
    }

    public function testItRejectsBlankSignatureSecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka consumer signatureSecret must be a non-empty string.');

        new KafkaConsumer(new KafkaConfig(['brokers' => 'kafka:9092']), 'group', ' ');
    }

    public function testItRejectsInvalidPollTimeoutBeforeTalkingToKafka(): void
    {
        $consumer = (new KafkaConsumer(
            new KafkaConfig(['brokers' => 'kafka:9092']),
            'group',
            'secret',
        ))->withTopics(['orders']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Poll timeout must be greater than zero milliseconds.');

        $consumer->runOnce(static fn(): bool => true, 0);
    }
}
