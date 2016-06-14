<?php

use \PhpAmqpLib\Connection\AMQPStreamConnection;
use \PhpAmqpLib\Message\AMQPMessage;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Message/SimpleMessage.php';

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest', '/');
$channel = $connection->channel();
$message = new \Beholder\Message\SimpleMessage();
$msg = new AMQPMessage($message->create(['text' => 'Hello']));
$channel->basic_publish($msg, '', 'parserQ');
