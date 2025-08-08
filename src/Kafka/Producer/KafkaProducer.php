<?php

declare(strict_types=1);

namespace Marwa\Kafka;

use Marwa\Kafka\DTO\Message;
use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Kafka\Contracts\ProducerInterface;

use RdKafka\Conf;
use RdKafka\Producer as RdKafkaProducer;
use RdKafka\ProducerTopic;
use RdKafka\KafkaConsumer as RdKafkaConsumer;
use Marwa\Envelop\EnvelopBuilder;
use Marwa\Envelop\Envelop;

final class KafkaProducer implements ProducerInterface
{
    private ?RdKafkaProducer $producer = null;
    private array $topics = [];

    public function __construct(
        private readonly KafkaConfig $config,
        private readonly string $signatureSecret
    ) {}

    public function produce(Message $message): void
    {
        $envelop = EnvelopBuilder::start()
            ->type($message->headers['type'] ?? 'event')
            ->sender($message->headers['sender'] ?? 'php-app')
            ->receiver($message->headers['receiver'] ?? 'service')
            ->body($message->payload)
            ->ttl($message->headers['ttl'] ?? 300)
            ->sign($this->signatureSecret)
            ->build();

        $payload = $envelop->toJson();
        $topic = $this->getTopic($message->topic);

        $topic->producev(
            $message->partition ?? RD_KAFKA_PARTITION_UA,
            0,
            $payload,
            $message->key,
            null,
            $message->timestampMs
        );

        $this->getProducer()->poll(0);
    }

    public function flush(int $timeoutMs = 10000): void
    {
        $this->getProducer()->flush($timeoutMs);
    }

    private function getProducer(): RdKafkaProducer
    {
        if ($this->producer) {
            return $this->producer;
        }

        $conf = new Conf();
        $conf->set('bootstrap.servers', $this->config->brokers);
        if ($this->config->clientId) {
            $conf->set('client.id', $this->config->clientId);
        }

        foreach ($this->config->extra as $key => $value) {
            $conf->set((string)$key, (string)$value);
        }

        return $this->producer = new RdKafkaProducer($conf);
    }

    private function getTopic(string $name): ProducerTopic
    {
        return $this->topics[$name] ??= $this->getProducer()->newTopic($name);
    }
}
