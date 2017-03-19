<?php

namespace Beholder;

use Beholder\Message\AdminMinionStatus;
use Beholder\Message\BeholderAdminKill;
use Beholder\Message\BeholderStatusGet;
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
            $messageTemplate = new AdminMinionStatus();
            $m = new MQMessage($message->body);
            if ($messageTemplate->validate($m)) {
                $ret = $m->get('minions');
                $this->channel->basic_cancel($tag);
            }
        };
        $tag = $this->channel->basic_consume($this->myQueueName, '', false, false, false, false, $fn);
        $message = new BeholderStatusGet();
        $msg = new AMQPMessage($message->create(['queue' => $this->myQueueName]));
        $this->channel->basic_publish($msg, '', $this->adminQueue);
        while (count($this->channel->callbacks)) {
            $this->channel->wait(null, false, 3);
        }
        return $ret;
    }

    public function kill($host, $pid, $role)
    {
        $message = new BeholderAdminKill();
        $msg = new AMQPMessage($message->create(['role' => $role, 'hostname' => $host, 'pid' => $pid]));
        $this->channel->basic_publish($msg, '', $this->adminQueue);
    }
}
