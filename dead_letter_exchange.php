<?php

require_once __DIR__ . '/dead_letter_exchange_config.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
$channel = $connection->channel();

//声明普通Exchange
$channel->exchange_declare(BUSINESS_EXCHANGE, 'direct', false, false, false);

$message = new AMQPMessage('hello world test B');
$channel->basic_publish($message,BUSINESS_EXCHANGE,BUSINESS_QUEUEB_ROUTING_KEY);

$channel->close();
$connection->close();

