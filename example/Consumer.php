<?php
require './vendor/autoload.php';

use Marwa\Kafka\Consumer\KafkaConsumer;
use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Envelop\Envelop;

$config = new KafkaConfig([
    'brokers' => 'kafka:9092',
    'clientId' => 'php-consumer'
]);

$consumer = (new KafkaConsumer($config, 'php-group', 'super-secret'))
    ->withHost('kafka:9092')
    ->withTopics(['user-events']);

$consumer->run(function ($envelop) {
    echo "📩 Received Message:\n";
    echo "  Type     : {$envelop->type}\n";
    echo "  Sender   : {$envelop->sender}\n";
    echo "  Body     : " . json_encode($envelop->body, JSON_PRETTY_PRINT) . "\n\n";

    return true; // commit the message
});
