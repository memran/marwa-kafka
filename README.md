# Marwa Kafka

![License](https://img.shields.io/github/license/memran/marwa-kafka)
[![Total Downloads](https://img.shields.io/packagist/dt/memran/marwa-kafka.svg?style=flat-square)](https://packagist.org/packages/memran/marwa-kafka)
![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue)
![Kafka](https://img.shields.io/badge/Kafka-Ready-orange)

A lightweight, PSR-4 compliant Kafka producer/consumer library for PHP, powered by [php-rdkafka](https://github.com/arnaud-lb/php-rdkafka) and enriched with secure message envelopes from [`memran/marwa-envelop`](https://github.com/memran/marwa-envelop).

---

## ✨ Features

- ✅ Lazy-loaded Kafka producer and consumer
- ✅ Secure messaging with TTL, signature & type using `Envelop`
- ✅ PSR-4 structure and clean, testable design
- ✅ CLI and Web-compatible
- ✅ KISS & DRY principles applied
- ✅ Plug-and-play Docker + Kafka setup

---

## 📦 Installation

```bash
composer require memran/marwa-kafka
```

Also ensure you have the `rdkafka` extension installed:

```bash
pecl install rdkafka
```

---

## 🐳 Quick Start (Docker Dev Environment)

### 1. Clone and start Kafka + PHP containers

```bash
git clone https://github.com/memran/marwa-kafka.git
cd marwa-kafka
docker compose up -d --build
```

### 2. Enter PHP CLI container

```bash
docker compose exec php bash
```

---

## 🧩 Usage Example

### Produce Message

```php
use Marwa\Kafka\KafkaProducer;
use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Kafka\DTO\Message;

$config = new KafkaConfig('kafka:9092', 'php-client');
$producer = new KafkaProducer($config, 'my-signature-key');

$producer->produce(new Message(
    topic: 'test-topic',
    payload: ['hello' => 'kafka'],
    key: 'my-key',
    headers: ['type' => 'event', 'sender' => 'php']
));
$producer->flush();
```

### Consume Message

```php
use Marwa\Kafka\KafkaConsumer;
use Marwa\Kafka\Support\KafkaConfig;

$config = new KafkaConfig('kafka:9092');
$consumer = new KafkaConsumer(
    config: $config,
    groupId: 'my-group',
    topics: ['test-topic'],
    signatureSecret: 'my-signature-key'
);

$consumer->run(function ($msg) {
    print_r($msg['body']);
});
```

---

## 📂 Directory Structure

```
src/
├── KafkaProducer.php
├── KafkaConsumer.php
├── DTO/Message.php
├── Support/KafkaConfig.php
└── Contracts/
    ├── ProducerInterface.php
    └── ConsumerInterface.php
```

---

## 🧪 Testing

```bash
composer install
vendor/bin/phpunit
```

---

## 📋 License

Licensed under the [MIT License](LICENSE).

---

## 🧠 Related Projects

- [`memran/marwa-envelop`](https://github.com/memran/marwa-envelop): Secure message envelope for Kafka, WebSocket, and HTTP.

---
