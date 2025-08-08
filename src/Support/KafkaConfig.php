<?php

declare(strict_types=1);

namespace Marwa\Kafka\Support;

/**
 * Class KafkaConfig
 *
 * Configuration container for Kafka client settings.
 */
class KafkaConfig
{
    /**
     * Kafka bootstrap servers (e.g., "kafka:9092")
     */
    public string $brokers;

    /**
     * Client identifier (used in Kafka logs and metrics)
     */
    public ?string $clientId = null;

    /**
     * Additional Kafka configuration options
     */
    public array $extra = [];

    /**
     * @param array{
     *     brokers: string,
     *     clientId?: string,
     *     extra?: array<string, string>
     * } $config
     */
    public function __construct(array $config)
    {
        if (empty($config['brokers'])) {
            throw new \InvalidArgumentException('KafkaConfig requires "brokers" key.');
        }

        $this->brokers = $config['brokers'];
        $this->clientId = $config['clientId'] ?? null;
        $this->extra = $config['extra'] ?? [];
    }

    /**
     * Convert to associative array (for logging or export)
     */
    public function toArray(): array
    {
        return [
            'brokers' => $this->brokers,
            'clientId' => $this->clientId,
            'extra'    => $this->extra,
        ];
    }
}
