<?php
require_once './vendor/autoload.php';

use Marwa\Kafka\Producer\KafkaProducer;
use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Envelop\EnvelopBuilder;

$config = new KafkaConfig([
    'brokers' => 'kafka:9092',
    'clientId' => 'php-producer'
]);

$producer = (new KafkaProducer($config))
    ->withHost('kafka:9092')
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
