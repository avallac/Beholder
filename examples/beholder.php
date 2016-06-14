<?php

use \PhpAmqpLib\Connection\AMQPStreamConnection;
use \Beholder\Beholder;

require_once __DIR__ . '/../vendor/autoload.php';

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest', '/');
$beholder = new Beholder($connection, 'testAdmin');
$beholder->addHandler(function ($host, $role, $status) {
    print "change in bd host:".$host." role:".$role." status".$status."\n";
});
$beholder->run();
