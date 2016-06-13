<?php

use \PhpAmqpLib\Connection\AMQPStreamConnection;
use \PhpAmqDaemonManager\Minion;
use \PhpAmqDaemonManager\Message\SimpleMessage;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Message/SimpleMessage.php';

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest', '/');
$agent = new Minion($connection, 'testhost', 'testAdmin');
$agent->bindRole('parser', 'parserQ');
$agent->getRole('parser')['messageManager']->bind(new SimpleMessage(
    function ($m) {
        var_dump($m);
    }
));
$agent->run();
