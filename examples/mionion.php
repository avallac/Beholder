<?php

use \PhpAmqpLib\Connection\AMQPStreamConnection;
use \Beholder\Minion;
use \Beholder\Message\SimpleMessage;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Message/SimpleMessage.php';

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest', '/');
$minion = new Minion($connection, 'testhost', 1, 'testAdmin');
$minion->bindRole('parser', 'parserQ');
$minion->getRole('parser')['messageManager']->bind(new SimpleMessage(
    function ($m) {
        var_dump($m);
    }
));
$minion->run();
