<?php

declare(strict_types=1);

namespace Marwa\Kafka\Producer;

use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Kafka\Contracts\ProducerInterface;
use RdKafka\Conf;
use RdKafka\Producer as RdKafkaProducer;
use RdKafka\ProducerTopic;
use Marwa\Envelop\EnvelopBuilder;

final class KafkaProducer implements ProducerInterface
{
    private ?RdKafkaProducer $producer = null;
    private array $topics = [];

    public function __construct(
        private readonly KafkaConfig $config
    ) {}

    public function produce(string $topic, EnvelopBuilder $envelop, ?string $key = null, ?int $timestampMs = null, ?int $partition = null): void
    {
        $payload = $envelop;
        $this->getTopic($topic)->producev(
            $partition ?? RD_KAFKA_PARTITION_UA,
            0,
            $payload,
            $key,
            null,
            $timestampMs
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
