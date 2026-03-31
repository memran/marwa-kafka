# Marwa Kafka

[![CI](https://github.com/memran/marwa-kafka/actions/workflows/ci.yml/badge.svg)](https://github.com/memran/marwa-kafka/actions/workflows/ci.yml)
![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)
![Kafka](https://img.shields.io/badge/Kafka-Ready-orange)

`memran/marwa-kafka` is a lightweight Kafka producer/consumer library for PHP built on `php-rdkafka` and `memran/marwa-envelop`. It keeps the public API small while enforcing safer configuration defaults and message validation.

## Requirements

- PHP 8.1, 8.2, or 8.3
- `ext-rdkafka`
- A reachable Kafka broker
- `memran/marwa-envelop`

Install the package:

```bash
composer require memran/marwa-kafka
```

Install the PHP extension if needed:

```bash
pecl install rdkafka
```

## Quick Start

### Produce a signed message

```php
<?php

declare(strict_types=1);

use Marwa\Envelop\EnvelopBuilder;
use Marwa\Kafka\Producer\KafkaProducer;
use Marwa\Kafka\Support\KafkaConfig;

$config = new KafkaConfig([
    'brokers' => 'kafka:9092',
    'clientId' => 'producer-app',
]);

$producer = (new KafkaProducer($config))
    ->withTopics(['user-events']);

$envelop = EnvelopBuilder::start()
    ->type('event')
    ->sender('php-app')
    ->receiver('user-service')
    ->body(['message' => 'Hello from PHP'])
    ->ttl(300)
    ->sign('super-secret')
    ->build();

$producer->produce('user-events', $envelop, 'user-123');
$producer->flush();
```

### Consume and validate messages

```php
<?php

declare(strict_types=1);

use Marwa\Kafka\Consumer\KafkaConsumer;
use Marwa\Kafka\Support\KafkaConfig;

$config = new KafkaConfig([
    'brokers' => 'kafka:9092',
    'clientId' => 'consumer-app',
]);

$consumer = (new KafkaConsumer($config, 'php-group', 'super-secret'))
    ->withTopics(['user-events'])
    ->withErrorHandler(static function (\Throwable $exception): void {
        error_log($exception->getMessage());
    });

$consumer->run(static function ($envelop): bool {
    var_dump($envelop->body);

    return true;
});
```

Messages with invalid signatures or expired envelopes are ignored safely. When auto-commit is disabled, returning `false` from the callback prevents manual commit.

## Configuration

`KafkaConfig` accepts:

- `brokers`: required bootstrap server list, for example `kafka:9092`
- `clientId`: optional Kafka client ID
- `extra`: optional associative array of additional Kafka settings

The library trims and validates broker names, host overrides, topic names, consumer group IDs, and signature secrets. Empty values are rejected early with `InvalidArgumentException`.

## Project Structure

```text
src/
  Consumer/
  Contracts/
  Producer/
  Support/
example/
tests/
```

## Development

Start the local Kafka stack:

```bash
docker compose up -d --build
docker compose exec php sh
```

Common Composer scripts:

```bash
composer test
composer test:coverage
composer analyse
composer lint
composer fix
composer ci
```

## Testing and Static Analysis

- PHPUnit 10 covers configuration and validation behavior.
- PHPStan runs at level 8.
- PHP-CS-Fixer enforces a consistent PSR-style code format.

## CI

GitHub Actions runs `composer ci` on pull requests and pushes to `main` across PHP 8.1, 8.2, and 8.3 with the `rdkafka` extension enabled.

## Security Notes

- Do not hard-code production secrets in examples or application code.
- Always use a strong `signatureSecret`.
- Prefer environment-specific broker configuration and Kafka ACLs.
- Review `extra` Kafka options before enabling delivery or SASL settings in production.

## Contributing

Open a pull request with a clear summary, test results, and any public API or README updates that accompany behavior changes.

## License

Released under the [MIT License](LICENSE).
