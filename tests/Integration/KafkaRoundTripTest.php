<?php

declare(strict_types=1);

namespace Marwa\Kafka\Tests\Integration;

use Marwa\Envelop\Envelop;
use Marwa\Envelop\EnvelopBuilder;
use Marwa\Kafka\Consumer\KafkaConsumer;
use Marwa\Kafka\Producer\KafkaProducer;
use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Kafka\Tests\Support\InMemoryLogger;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
final class KafkaRoundTripTest extends TestCase
{
    public function testProducerAndConsumerCanRoundTripAnEnvelope(): void
    {
        if (!extension_loaded('rdkafka')) {
            self::markTestSkipped('The rdkafka extension is required for integration tests.');
        }

        $brokers = getenv('KAFKA_BROKERS') ?: 'kafka:9092';
        $signatureSecret = getenv('KAFKA_SIGNATURE_SECRET') ?: 'integration-secret';
        $topic = 'integration-topic-' . bin2hex(random_bytes(6));
        $groupId = 'integration-group-' . bin2hex(random_bytes(6));

        $config = new KafkaConfig([
            'brokers' => $brokers,
            'clientId' => 'integration-test-client',
        ]);

        $producer = (new KafkaProducer($config))
            ->withTopics([$topic]);

        $consumer = (new KafkaConsumer($config, $groupId, $signatureSecret, false))
            ->withTopics([$topic]);

        $expectedBody = [
            'event' => 'user.created',
            'userId' => '42',
        ];

        $producer->produce(
            $topic,
            EnvelopBuilder::start()
                ->type('event')
                ->sender('phpunit')
                ->receiver('integration-consumer')
                ->body($expectedBody)
                ->ttl(300)
                ->sign($signatureSecret)
                ->build(),
            'user-42',
        );
        $producer->flush();

        $received = null;

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $consumer->runOnce(static function (Envelop $envelop) use (&$received): bool {
                $received = $envelop;

                return true;
            }, 500);

            if ($received instanceof Envelop) {
                break;
            }

            usleep(250_000);
        }

        self::assertInstanceOf(Envelop::class, $received);
        self::assertSame($expectedBody, $received->body);
        self::assertSame('event', $received->type);
        self::assertSame('phpunit', $received->sender);
    }

    public function testConsumerLogsInvalidSignatureMessages(): void
    {
        if (!extension_loaded('rdkafka')) {
            self::markTestSkipped('The rdkafka extension is required for integration tests.');
        }

        $brokers = getenv('KAFKA_BROKERS') ?: 'kafka:9092';
        $topic = 'integration-topic-' . bin2hex(random_bytes(6));
        $groupId = 'integration-group-' . bin2hex(random_bytes(6));
        $logger = new InMemoryLogger();

        $config = new KafkaConfig([
            'brokers' => $brokers,
            'clientId' => 'integration-test-client',
        ]);

        $producer = (new KafkaProducer($config))
            ->withTopics([$topic]);

        $consumer = (new KafkaConsumer($config, $groupId, 'expected-secret', false))
            ->withTopics([$topic])
            ->withLogger($logger);

        $producer->produce(
            $topic,
            EnvelopBuilder::start()
                ->type('event')
                ->sender('phpunit')
                ->receiver('integration-consumer')
                ->body(['event' => 'invalid-signature'])
                ->ttl(300)
                ->sign('wrong-secret')
                ->build(),
        );
        $producer->flush();

        $handled = false;

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $consumer->runOnce(static function () use (&$handled): bool {
                $handled = true;

                return true;
            }, 500);

            if ($logger->records !== []) {
                break;
            }

            usleep(250_000);
        }

        self::assertFalse($handled);
        self::assertNotEmpty($logger->records);
        self::assertSame('warning', $logger->records[0]['level']);
        self::assertSame('Skipping Kafka message with invalid envelope signature.', $logger->records[0]['message']);
    }
}
