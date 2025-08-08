<?php

declare(strict_types=1);

namespace Marwa\Kafka\Consumer;

use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Kafka\Contracts\ConsumerInterface;
use RdKafka\Conf;
use RdKafka\KafkaConsumer as RdKafkaConsumer;
use RdKafka\Message as KafkaMessage;
use Marwa\Envelop\Envelop;

/**
 * Class KafkaConsumer
 *
 * Consumes and processes Kafka messages using Envelop structure.
 */
final class KafkaConsumer implements ConsumerInterface
{
    private ?RdKafkaConsumer $consumer = null;
    private bool $running = false;
    private ?string $kafkaHost = null;
    private array $topicList = [];

    public function __construct(
        private readonly KafkaConfig $config,
        private readonly string $groupId,
        private readonly string $signatureSecret,
        private readonly bool $enableAutoCommit = true,
        private readonly string $autoOffsetReset = 'earliest'
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

    public function listen(callable $onMessage, int $pollTimeoutMs = 1000): void
    {
        $this->run($onMessage, $pollTimeoutMs);
    }


    public function run(callable $onMessage, int $pollTimeoutMs = 1000): void
    {
        $this->running = true;

        while ($this->running) {
            $this->runOnce($onMessage, $pollTimeoutMs);
        }
    }

    public function runOnce(callable $onMessage, int $pollTimeoutMs = 500): bool
    {
        $message = $this->getConsumer()->consume($pollTimeoutMs);

        if (!$message instanceof KafkaMessage) {
            return false;
        }


        return $this->handleMessage($message, $onMessage);
    }

    public function stop(): void
    {
        $this->running = false;
    }

    private function handleMessage(KafkaMessage $msg, callable $onMessage): bool
    {
        switch ($msg->err) {
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                try {
                    $envelop = Envelop::fromJson((string) $msg->payload);

                    if ($envelop->isExpired() || !$envelop->checkSignature($this->signatureSecret)) {
                        return true;
                    }

                    $ok = $onMessage($envelop);

                    if ($ok !== false && !$this->enableAutoCommit) {
                        $this->getConsumer()->commit($msg);
                    }

                    return true;
                } catch (\Throwable) {
                    return true;
                }

            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            case RD_KAFKA_RESP_ERR__TIMED_OUT:
            default:
                return false;
        }
    }

    private function getConsumer(): RdKafkaConsumer
    {
        if ($this->consumer !== null) {
            return $this->consumer;
        }

        $conf = new Conf();
        $conf->set('bootstrap.servers', $this->kafkaHost ?? $this->config->brokers);
        $conf->set('group.id', $this->groupId);
        $conf->set('enable.auto.commit', $this->enableAutoCommit ? 'true' : 'false');
        $conf->set('auto.offset.reset', $this->autoOffsetReset);


        if (!empty($this->config->clientId)) {
            $conf->set('client.id', $this->config->clientId);
        }

        foreach ($this->config->extra as $key => $value) {
            $conf->set((string) $key, (string) $value);
        }

        $consumer = new RdKafkaConsumer($conf);
        $consumer->subscribe($this->topicList);

        return $this->consumer = $consumer;
    }
}
