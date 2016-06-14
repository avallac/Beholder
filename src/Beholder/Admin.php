<?php

namespace Beholder;

use PhpAmqDaemonManager\Message\BeholderStatusGet;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Admin
{
    protected $myQueueName;
    protected $adminQueue;

    public function __construct(AbstractConnection $connection, $adminQueue)
    {
        $this->connection = $connection;
        $this->channel = $this->connection->channel();
        list($this->myQueueName, ,) = $this->channel->queue_declare("");
        $this->adminQueue = $adminQueue;
    }

    public function getInfo()
    {
        $ret = [];
        $tag = null;
        $fn = function ($message) use (&$ret, &$tag) {
            $m = new MQMessage($message->body);
            $ret = $m->get('text');
            $this->channel->basic_cancel($tag);
        };
        $tag = $this->channel->basic_consume($this->myQueueName, '', false, false, false, false, $fn);
        $message = new BeholderStatusGet();
        $msg = new AMQPMessage($message->create(['queue' => $this->myQueueName]));
        $this->channel->basic_publish($msg, '', $this->adminQueue);
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        return $ret;
    }
}
