<?php

/**
 * 实现原理：
 * 1、rabbitmq 可以针对 Queue和Message 设置  x-message-ttl来控制消息的生存时间，
 * 如果超时，消息变为  dead letter
 * 2、rabbitmq 的queue 可以配置 x-dead-letter-exchange 和 x-dead-letter-routing(可选)
 * 两个参数，来控制队列出现dead letter 的时候，重新发送消息的目的地
 * 
 * 注意事项：
 * 1、设置了x-dead-letter-exchange 和 x-dead-letter-routing 后的队列是根据
 * 队列入队的顺序进行消费，即使到了过期时间也不会触发x-dead-letter-exchange
 * 因为过期时间是在消息出队列的时候进行判断的
 * 2、所以当队列没有设过期时间时，插入一个没有过期时间的消息会导致 x-dead-letter-exchange
 * 队列永远不会被消费
 *
 * https://www.rabbitmq.com/ttl.html
 */

require_once __DIR__ . '/dead_letter_exchange_config.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('delay_exchange', 'direct',false,false,false);
$channel->exchange_declare('cache_exchange', 'direct',false,false,false);

// 对queue设置最大过期时间
$tale = new AMQPTable();
$tale->set('x-dead-letter-exchange', 'delay_exchange');
$tale->set('x-dead-letter-routing-key','delay_exchange_key');
$tale->set('x-message-ttl',10000);

$channel->queue_declare('cache_queue',false,true,false,false,false,$tale);
$channel->queue_bind('cache_queue', 'cache_exchange','cache_exchange_key');

$channel->queue_declare('delay_queue',false,true,false,false,false);
$channel->queue_bind('delay_queue', 'delay_exchange','delay_exchange_key');

// 对发送的每个Message 设置过期时间（milliseconds）
$msg = new AMQPMessage('Hello World'.$argv[1],array(
    //'expiration' => intval($argv[1]),
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT

));

$channel->basic_publish($msg,'cache_exchange','cache_exchange_key');
echo date('Y-m-d H:i:s')." [x] Sent 'Hello World!' ".PHP_EOL;

$channel->close();
$connection->close();

