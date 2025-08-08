<?php

namespace Marwa\Kafka\Producer;

use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Kafka\Contracts\ProducerInterface;
use RdKafka\Conf;
use RdKafka\Producer as RdKafkaProducer;
use RdKafka\ProducerTopic;
use Marwa\Envelop\Envelop;

/**
 * Class KafkaProducer
 */
final class KafkaProducer implements ProducerInterface
{
    private ?RdKafkaProducer $producer = null;
    private array $topics = [];
    private ?string $kafkaHost = null;
    private array $topicList = [];

    public function __construct(
        private readonly KafkaConfig $config
    ) {}

    public function withHost(string $host): self
    {
        $this->kafkaHost = $host;
        return $this;
    }

    public function withTopics(array $topics): self
    {
        $this->topicList = $topics;
        return $this;
    }

    public function produce(
        string $topic,
        Envelop $envelop,
        ?string $key = null,
        ?int $timestampMs = null,
        ?int $partition = null
    ): void {
        $this->getTopic($topic)->producev(
            $partition ?? RD_KAFKA_PARTITION_UA,
            0,
            $envelop->toJson(),
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
        if ($this->producer !== null) {
            return $this->producer;
        }

        $conf = new Conf();
        $conf->set('bootstrap.servers', $this->kafkaHost ?? $this->config->brokers);

        if (!empty($this->config->clientId)) {
            $conf->set('client.id', $this->config->clientId);
        }

        foreach ($this->config->extra as $key => $value) {
            $conf->set((string) $key, (string) $value);
        }

        return $this->producer = new RdKafkaProducer($conf);
    }

    private function getTopic(string $name): ProducerTopic
    {
        return $this->topics[$name] ??= $this->getProducer()->newTopic($name);
    }
}
