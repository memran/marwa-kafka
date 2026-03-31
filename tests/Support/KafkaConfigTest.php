<?php

declare(strict_types=1);

namespace Marwa\Kafka\Tests\Support;

use Marwa\Kafka\Support\KafkaConfig;
use PHPUnit\Framework\TestCase;

final class KafkaConfigTest extends TestCase
{
    public function testItNormalizesWhitespaceAndScalarOptions(): void
    {
        $config = new KafkaConfig([
            'brokers' => ' kafka:9092 ',
            'clientId' => ' producer-app ',
            'extra' => [
                ' compression.type ' => 'gzip',
                'socket.keepalive.enable' => true,
                'metadata.max.age.ms' => 60000,
                'nullable.option' => null,
            ],
        ]);

        self::assertSame('kafka:9092', $config->brokers);
        self::assertSame('producer-app', $config->clientId);
        self::assertSame([
            'compression.type' => 'gzip',
            'socket.keepalive.enable' => '1',
            'metadata.max.age.ms' => '60000',
            'nullable.option' => '',
        ], $config->extra);
    }

    public function testItDropsBlankClientId(): void
    {
        $config = new KafkaConfig([
            'brokers' => 'kafka:9092',
            'clientId' => '   ',
        ]);

        self::assertNull($config->clientId);
    }

    public function testItRejectsMissingBrokers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('KafkaConfig requires "brokers" key.');

        new KafkaConfig([]);
    }

    public function testItRejectsInvalidExtraValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('KafkaConfig "extra" values must be scalar or null.');

        new KafkaConfig([
            'brokers' => 'kafka:9092',
            'extra' => [
                'bad' => ['nested'],
            ],
        ]);
    }
}
