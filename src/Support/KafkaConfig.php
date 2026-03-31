<?php

declare(strict_types=1);

namespace Marwa\Kafka\Support;

final class KafkaConfig
{
    public string $brokers;
    public ?string $clientId = null;

    /** @var array<string, string> */
    public array $extra = [];

    /**
     * @param array{
     *     brokers?: string,
     *     clientId?: string,
     *     extra?: mixed
     * } $config
     */
    public function __construct(array $config)
    {
        $brokers = trim((string) ($config['brokers'] ?? ''));

        if ($brokers === '') {
            throw new \InvalidArgumentException('KafkaConfig requires "brokers" key.');
        }

        $clientId = isset($config['clientId']) ? trim((string) $config['clientId']) : null;

        $this->brokers = $brokers;
        $this->clientId = $clientId !== '' ? $clientId : null;
        $this->extra = $this->normalizeExtraOptions($config['extra'] ?? []);
    }

    /**
     * @return array{brokers: string, clientId: ?string, extra: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'brokers' => $this->brokers,
            'clientId' => $this->clientId,
            'extra' => $this->extra,
        ];
    }

    /**
     * @param mixed $extra
     * @return array<string, string>
     */
    private function normalizeExtraOptions(mixed $extra): array
    {
        if (!is_array($extra)) {
            throw new \InvalidArgumentException('KafkaConfig "extra" must be an associative array.');
        }

        $normalized = [];

        foreach ($extra as $key => $value) {
            if (trim((string) $key) === '') {
                throw new \InvalidArgumentException('KafkaConfig "extra" keys must be non-empty strings.');
            }

            if (!is_scalar($value) && $value !== null) {
                throw new \InvalidArgumentException('KafkaConfig "extra" values must be scalar or null.');
            }

            $normalized[trim((string) $key)] = $value === null ? '' : (string) $value;
        }

        return $normalized;
    }
}
