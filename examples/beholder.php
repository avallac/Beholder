<?php

use \PhpAmqpLib\Connection\AMQPStreamConnection;
use \PhpAmqDaemonManager\Beholder;

require_once __DIR__ . '/../vendor/autoload.php';

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest', '/');
$agent = new Beholder($connection, 'testAdmin');
$agent->addHandler(function ($host, $role, $status) {
    print "change in bd host:".$host." role:".$role." status".$status."\n";
});
$agent->run();
