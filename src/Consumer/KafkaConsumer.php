<?php

declare(strict_types=1);

namespace Marwa\Kafka\Consumer;

use Marwa\Envelop\Envelop;
use Marwa\Kafka\Contracts\ConsumerInterface;
use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Kafka\Support\Topic;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RdKafka\Conf;
use RdKafka\KafkaConsumer as RdKafkaConsumer;
use RdKafka\Message as KafkaMessage;

final class KafkaConsumer implements ConsumerInterface
{
    private ?RdKafkaConsumer $consumer = null;
    private bool $running = false;
    private ?string $kafkaHost = null;

    /** @var list<string> */
    private array $topicList = [];

    /** @var null|callable(\Throwable): void */
    private $errorHandler = null;
    private LoggerInterface $logger;

    public function __construct(
        private readonly KafkaConfig $config,
        private readonly string $groupId,
        private readonly string $signatureSecret,
        private readonly bool $enableAutoCommit = true,
        private readonly string $autoOffsetReset = 'earliest',
    ) {
        $this->logger = new NullLogger();

        if (trim($this->groupId) === '') {
            throw new \InvalidArgumentException('Kafka consumer groupId must be a non-empty string.');
        }

        if (trim($this->signatureSecret) === '') {
            throw new \InvalidArgumentException('Kafka consumer signatureSecret must be a non-empty string.');
        }

        if (trim($this->autoOffsetReset) === '') {
            throw new \InvalidArgumentException('Kafka consumer autoOffsetReset must be a non-empty string.');
        }
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

    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param list<string> $topics
     */
    public function withTopics(array $topics): self
    {
        $this->topicList = Topic::normalizeList($topics);

        return $this;
    }

    public function listen(callable $onMessage, int $pollTimeoutMs = 1000): void
    {
        $this->run($onMessage, $pollTimeoutMs);
    }

    public function withErrorHandler(callable $errorHandler): self
    {
        $this->errorHandler = $errorHandler;

        return $this;
    }

    public function run(callable $onMessage, int $pollTimeoutMs = 1000): void
    {
        $this->assertPollTimeout($pollTimeoutMs);
        $this->running = true;

        while ($this->shouldContinueRunning()) {
            $this->runOnce($onMessage, $pollTimeoutMs);
        }
    }

    public function runOnce(callable $onMessage, int $pollTimeoutMs = 500): bool
    {
        $this->assertPollTimeout($pollTimeoutMs);

        $message = $this->getConsumer()->consume($pollTimeoutMs);

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

                    if ($envelop->isExpired()) {
                        $this->logger->warning('Skipping expired Kafka message.', [
                            'topic' => $msg->topic_name,
                            'partition' => $msg->partition,
                            'offset' => $msg->offset,
                        ]);

                        return true;
                    }

                    if (!$envelop->checkSignature($this->signatureSecret)) {
                        $this->logger->warning('Skipping Kafka message with invalid envelope signature.', [
                            'topic' => $msg->topic_name,
                            'partition' => $msg->partition,
                            'offset' => $msg->offset,
                        ]);

                        return true;
                    }

                    $ok = $onMessage($envelop);

                    if ($ok !== false && !$this->enableAutoCommit) {
                        $this->getConsumer()->commit($msg);
                    }

                    return true;
                } catch (\Throwable $exception) {
                    $this->logger->error('Kafka consumer failed to process a message.', [
                        'exception' => $exception,
                        'topic' => $msg->topic_name,
                        'partition' => $msg->partition,
                        'offset' => $msg->offset,
                    ]);

                    if ($this->errorHandler !== null) {
                        ($this->errorHandler)($exception);
                    }

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

        if ($this->topicList === []) {
            throw new \LogicException('Kafka consumer requires at least one topic. Call withTopics() before consuming.');
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

    private function assertPollTimeout(int $pollTimeoutMs): void
    {
        if ($pollTimeoutMs <= 0) {
            throw new \InvalidArgumentException('Poll timeout must be greater than zero milliseconds.');
        }
    }

    private function shouldContinueRunning(): bool
    {
        return $this->running;
    }
}
