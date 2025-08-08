<?php
require '../vendor/autoload.php';

use Marwa\Kafka\KafkaProducer;
use Marwa\Kafka\Support\KafkaConfig;
use Marwa\Envelop\Envelop;


$config = new KafkaConfig('kafka:9092');
$consumer = new KafkaConsumer(
    config: $config,
    groupId: 'php-group',
    topics: ['test-topic'],
    signatureSecret: 'secret-signature-key'
);

echo "👂 Listening for messages...
";

$consumer->run(function (Envelop $envelop) {
    print_r($envelop->body);
});
