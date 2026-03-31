<?php

declare(strict_types=1);

namespace Marwa\Kafka\Producer;

use Marwa\Envelop\Envelop;
use Marwa\Kafka\Contracts\ProducerInterface;
use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Kafka\Support\Topic;
use RdKafka\Conf;
use RdKafka\Producer as RdKafkaProducer;
use RdKafka\ProducerTopic;

final class KafkaProducer implements ProducerInterface
{
    private ?RdKafkaProducer $producer = null;

    /** @var array<string, ProducerTopic> */
    private array $topics = [];
    private ?string $kafkaHost = null;

    /** @var list<string> */
    private array $topicList = [];

    public function __construct(
        private readonly KafkaConfig $config,
    ) {
    }

    public function withHost(string $host): self
    {
        $normalizedHost = trim($host);

        if ($normalizedHost === '') {
            throw new \InvalidArgumentException('Kafka host must be a non-empty string.');
        }

        $this->kafkaHost = $normalizedHost;

        return $this;
    }

    /**
     * @param list<string> $topics
     */
    public function withTopics(array $topics): self
    {
        $this->topicList = Topic::normalizeList($topics);
        $this->warmConfiguredTopics();

        return $this;
    }

    public function produce(
        string $topic,
        Envelop $envelop,
        ?string $key = null,
        ?int $timestampMs = null,
        ?int $partition = null,
    ): void {
        $normalizedTopic = Topic::normalize($topic);

        $topicHandle = $this->getTopic($normalizedTopic);

        if ($timestampMs === null) {
            $topicHandle->producev(
                $partition ?? RD_KAFKA_PARTITION_UA,
                0,
                $envelop->toJson(),
                $key,
            );
        } else {
            $topicHandle->producev(
                $partition ?? RD_KAFKA_PARTITION_UA,
                0,
                $envelop->toJson(),
                $key,
                null,
                $timestampMs,
            );
        }

        $this->getProducer()->poll(0);
    }

    public function flush(int $timeoutMs = 10000): void
    {
        if ($timeoutMs <= 0) {
            throw new \InvalidArgumentException('Flush timeout must be greater than zero milliseconds.');
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $result = $this->getProducer()->flush($timeoutMs);

            if ($result === RD_KAFKA_RESP_ERR_NO_ERROR) {
                return;
            }
        }

        throw new \RuntimeException('Unable to flush Kafka producer queue within the configured timeout.');
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

        $producer = new RdKafkaProducer($conf);
        $this->producer = $producer;
        $this->warmConfiguredTopics();

        return $producer;
    }

    private function getTopic(string $name): ProducerTopic
    {
        return $this->topics[$name] ??= $this->getProducer()->newTopic($name);
    }

    private function warmConfiguredTopics(): void
    {
        if ($this->producer === null) {
            return;
        }

        foreach ($this->topicList as $topic) {
            $this->topics[$topic] ??= $this->producer->newTopic($topic);
        }
    }
}
