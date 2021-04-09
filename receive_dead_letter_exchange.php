<?php

require_once __DIR__ . '/dead_letter_exchange_config.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
$channel = $connection->channel();

//声明普通Exchange
$channel->exchange_declare(BUSINESS_EXCHANGE, 'direct', false, false, false);

//声明死信Exchange
$channel->exchange_declare(DEAD_LETTER_EXCHANGE,'direct');

//声明业务队列A
$channel->queue_declare(
    BUSINESS_QUEUEA,
    false,
    true,
    false,
    false,
    false,
    new AMQPTable([
        //'x-message-ttl'=>15000,
        'x-dead-letter-exchange'=>DEAD_LETTER_EXCHANGE,
        'x-dead-letter-routing-key'=>DEAD_LETTER_QUEUEA_ROUTING_KEY
    ])
);

//声明业务队列B
$channel->queue_declare(
    BUSINESS_QUEUEB,
    false,
    true,
    false,
    false,
    false,
    new AMQPTable([
        //'x-message-ttl'=>15000,
        'x-dead-letter-exchange'=>DEAD_LETTER_EXCHANGE,
        'x-dead-letter-routing-key'=>DEAD_LETTER_QUEUEA_ROUTING_KEY
    ])
);

//声明死信队列A
$channel->queue_declare(DEAD_LETTER_QUEUEA);

//声明死信队列B
//$channel->queue_declare(DEAD_LETTER_QUEUEB);

// 声明业务队列A绑定关系
$channel->queue_bind(BUSINESS_QUEUEA,BUSINESS_EXCHANGE,BUSINESS_QUEUEA_ROUTING_KEY);

// 声明业务队列B绑定关系
$channel->queue_bind(BUSINESS_QUEUEB,BUSINESS_EXCHANGE,BUSINESS_QUEUEB_ROUTING_KEY);

// 声明死信队列A绑定关系
$channel->queue_bind(DEAD_LETTER_QUEUEA,DEAD_LETTER_EXCHANGE,DEAD_LETTER_QUEUEA_ROUTING_KEY);

// 声明死信队列B绑定关系
//$channel->queue_bind(DEAD_LETTER_QUEUEB,DEAD_LETTER_EXCHANGE,DEAD_LETTER_QUEUEB_ROUTING_KEY);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function ($msg) {
    echo " [x] Received ", $msg->body, " on ", date('Y-m-d, H:i:s'),"\n";
    echo " [-] Cannot process crap. Nacking message. \n";
    $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
};

$channel->basic_consume(BUSINESS_QUEUEA, '', false, false, false, false, $callback);
$channel->basic_consume(BUSINESS_QUEUEB, '', false, false, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>
