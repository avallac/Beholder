<?php

use \PhpAmqpLib\Connection\AMQPStreamConnection;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Message/SimpleMessage.php';

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest', '/');
$admin = new \Beholder\Admin($connection, 'testAdmin');
var_dump($admin->getInfo());
