<?php
require_once './vendor/autoload.php';

use Marwa\Kafka\Producer\KafkaProducer;
use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Envelop\EnvelopBuilder;

$config = new KafkaConfig('kafka:9092', 'php-producer');
$producer = new KafkaProducer($config, 'secret-signature-key');

$msg = EnvelopBuilder::start()
    ->type('event')
    ->sender('php-app')
    ->receiver('my-service')
    ->body(['message' => 'Hello'])
    ->ttl(300)
    ->sign('secret')
    ->build();

$producer->produce('my-topic', $msg);

$producer->flush();

echo "✅ Message produced successfully.
";
