<?php
require 'vendor/autoload.php';

use Marwa\Kafka\KafkaProducer;
use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Kafka\DTO\Message;

$config = new KafkaConfig('kafka:9092', 'php-producer');
$producer = new KafkaProducer($config, 'secret-signature-key');

$message = new Message(
    topic: 'test-topic',
    payload: ['event' => 'user_registered', 'user_id' => 101],
    key: 'user-key',
    headers: ['type' => 'event', 'sender' => 'php-app', 'ttl' => 600]
);

$producer->produce($message);
$producer->flush();

echo "✅ Message produced successfully.
";
