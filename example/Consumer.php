<?php
require '../vendor/autoload.php';

use Marwa\Kafka\KafkaConsumer;
use Marwa\Kafka\Support\KafkaConfig;

$config = new KafkaConfig('kafka:9092');
$consumer = new KafkaConsumer(
    config: $config,
    groupId: 'php-group',
    topics: ['test-topic'],
    signatureSecret: 'secret-signature-key'
);

echo "👂 Listening for messages...
";

$consumer->run(function ($msg) {
    echo "📥 Received message:
";
    print_r($msg['body']);
    return true;
});
