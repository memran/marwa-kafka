<?php

declare(strict_types=1);

use Marwa\Kafka\Contracts\ConsumerInterface;
use Marwa\Kafka\DTO\Message;
use Marwa\Kafka\Support\KafkaConfig;

final class KafkaConsumer implements ConsumerInterface
{
    private ?RdKafkaConsumer $consumer = null;
    private bool $running = false;

    public function __construct(
        private readonly KafkaConfig $config,
        private readonly string $groupId,
        private readonly array $topics,
        private readonly string $signatureSecret,
        private readonly bool $enableAutoCommit = true,
        private readonly string $autoOffsetReset = 'earliest'
    ) {}

    public function run(callable $onMessage, int $pollTimeoutMs = 1000): void
    {
        $this->running = true;

        while ($this->running) {
            $this->runOnce($onMessage, $pollTimeoutMs);
        }
    }

    public function runOnce(callable $onMessage, int $pollTimeoutMs = 500): bool
    {
        $msg = $this->getConsumer()->consume($pollTimeoutMs);
        if ($msg === null) return false;

        switch ($msg->err) {
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                try {
                    $envelop = Envelop::fromJson((string)$msg->payload);

                    if ($envelop->isExpired() || !$envelop->checkSignature($this->signatureSecret)) {
                        return true;
                    }

                    $ok = $onMessage([
                        'type' => $envelop->type,
                        'sender' => $envelop->sender,
                        'receiver' => $envelop->receiver,
                        'headers' => $envelop->headers,
                        'body' => $envelop->body,
                        'raw' => $msg->payload
                    ]);

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

    public function stop(): void
    {
        $this->running = false;
    }

    private function getConsumer(): RdKafkaConsumer
    {
        if ($this->consumer) {
            return $this->consumer;
        }

        $conf = new Conf();
        $conf->set('bootstrap.servers', $this->config->brokers);
        $conf->set('group.id', $this->groupId);
        $conf->set('enable.auto.commit', $this->enableAutoCommit ? 'true' : 'false');
        $conf->set('auto.offset.reset', $this->autoOffsetReset);

        if ($this->config->clientId) {
            $conf->set('client.id', $this->config->clientId);
        }

        foreach ($this->config->extra as $key => $value) {
            $conf->set((string)$key, (string)$value);
        }

        $this->consumer = new RdKafkaConsumer($conf);
        $this->consumer->subscribe($this->topics);

        return $this->consumer;
    }
}
