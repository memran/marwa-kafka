<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Marwa\Envelop\EnvelopBuilder;
use Marwa\Kafka\Producer\KafkaProducer;
use Marwa\Kafka\Support\KafkaConfig;

$config = new KafkaConfig([
    'brokers' => 'kafka:9092',
    'clientId' => 'php-producer',
]);

$producer = (new KafkaProducer($config))
    ->withTopics(['user-events']);

for ($i = 0; $i < 50; $i++) {
    echo "Producing message $i...\n";
    $envelop = EnvelopBuilder::start()
        ->type('event')
        ->sender('php-app')
        ->receiver('user-service')
        ->body(['message' => 'Hello from PHP producer!-' . $i])
        ->ttl(300)
        ->sign('super-secret')
        ->build();

    $producer->produce('user-events', $envelop, 'user-123');
}
$producer->flush();

echo "✅ Message produced successfully.\n";
