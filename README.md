# Marwa Kafka

[![CI Status](https://github.com/memran/marwa-kafka/actions/workflows/ci.yml/badge.svg)](https://github.com/memran/marwa-kafka/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/memran/marwa-kafka.svg)](https://packagist.org/packages/memran/marwa-kafka)
[![Downloads](https://img.shields.io/packagist/dt/memran/marwa-kafka.svg)](https://packagist.org/packages/memran/marwa-kafka)
[![License](https://img.shields.io/github/license/memran/marwa-kafka)](LICENSE)

`memran/marwa-kafka` is a production-focused Kafka producer/consumer library for PHP. It wraps `php-rdkafka` with signed envelope handling from `memran/marwa-envelop`, safer configuration validation, PSR-3 logging hooks, and a lightweight developer workflow.

## Features

- Lazy Kafka producer and consumer setup with PSR-4 autoloading
- Envelope signing, signature validation, and TTL-aware message filtering
- Early validation for brokers, topics, group IDs, secrets, and timeout values
- Optional PSR-3 structured logging for invalid or failed consumer message handling
- PHPUnit, PHPStan, PHP-CS-Fixer, and GitHub Actions quality gates
- Real Kafka integration tests for Docker and CI environments with `ext-rdkafka`

## Requirements

- PHP 8.1, 8.2, or 8.3
- `ext-rdkafka`
- A reachable Kafka broker
- `memran/marwa-envelop`

Install the package:

```bash
composer require memran/marwa-kafka
```

Install the extension if needed:

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

$producer->produce(
    'user-events',
    EnvelopBuilder::start()
        ->type('event')
        ->sender('php-app')
        ->receiver('user-service')
        ->body(['message' => 'Hello from PHP'])
        ->ttl(300)
        ->sign('super-secret')
        ->build(),
    'user-123',
);

$producer->flush();
```

### Consume with PSR-3 logging

```php
<?php

declare(strict_types=1);

use Marwa\Kafka\Consumer\KafkaConsumer;
use Marwa\Kafka\Support\KafkaConfig;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

$logger = new Logger('kafka');
$logger->pushHandler(new StreamHandler('php://stderr', Level::Warning));

$config = new KafkaConfig([
    'brokers' => 'kafka:9092',
    'clientId' => 'consumer-app',
]);

$consumer = (new KafkaConsumer($config, 'php-group', 'super-secret'))
    ->withTopics(['user-events'])
    ->withLogger($logger);

$consumer->run(static function ($envelop): bool {
    var_dump($envelop->body);

    return true;
});
```

Expired messages and invalid signatures are skipped safely. When auto-commit is disabled, returning `false` from the callback prevents manual commit.

## Configuration

`KafkaConfig` accepts:

- `brokers`: required bootstrap server list such as `kafka:9092`
- `clientId`: optional Kafka client ID
- `extra`: optional associative array of extra Kafka client options

Empty broker strings, topic names, host overrides, group IDs, signature secrets, and invalid timeout values now fail fast with `InvalidArgumentException`.

## Project Structure

```text
src/
  Consumer/
  Contracts/
  Producer/
  Support/
example/
tests/
  Consumer/
  Producer/
  Support/
  Integration/
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
composer test:integration
composer test:coverage
composer analyse
composer lint
composer fix
composer ci
```

Run the integration suite in Docker:

```bash
docker compose exec php composer install --no-interaction --prefer-dist
docker compose exec php composer test:integration
```

## Testing and Static Analysis

- `composer test` runs the unit suite.
- `composer test:integration` runs real Kafka round-trip tests.
- `composer analyse` runs PHPStan 2.x.
- `composer lint` and `composer fix` run PHP-CS-Fixer.

## CI

GitHub Actions runs:

- A matrix quality job on PHP 8.1, 8.2, and 8.3
- A Docker-based Kafka integration job that boots the local stack and runs the integration suite inside the PHP container

## Security Notes

- Do not hard-code production secrets in application code.
- Use strong per-environment `signatureSecret` values.
- Prefer Kafka ACLs and environment-specific broker configuration.
- Review `extra` client options carefully before enabling SASL or delivery-related settings.

## Contributing

Open a pull request with a clear summary, test results, and any README or example updates required by public API changes.

## License

Released under the [MIT License](LICENSE).
