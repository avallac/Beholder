<?php

namespace PhpAmqDaemonManager;

use PhpAmqDaemonManager\Message\AbstractMessage;
use PhpAmqDaemonManager\Message\AgentStatusUpdate;
use PhpAmqDaemonManager\Message\BeholderStatusUpdate;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Beholder
{
    public $minions = [];
    public $messageManager;

    public function __construct(AbstractConnection $connection, $adminQueue)
    {
        $this->connection = $connection;
        $this->channel = $this->connection->channel();
        $this->adminQueue = $adminQueue;
        $this->messageManager = new MessageManager();
        
        $this->messageManager->bind(new BeholderStatusUpdate(function (MQMessage $message) {
            $host = $message->get('hostname');
            $role = $message->get('role');
            $q = $message->get('queue');
            if (!isset($this->minions[$host])) {
                $this->minions[$host] = [];
            }
            if (!isset($this->minions[$host][$role])) {
                $this->minions[$host][$role] = [];
            }
            $this->minions[$host][$role]['status'] = $message->get('status');
            $this->minions[$host][$role]['queue'] = $message->get('queue');
            $this->createMessage(new AgentStatusUpdate(), ['role' => $role, 'status' => 1], $q);
        }));

        $callbackMng = function ($rabbitMessage) {
            $this->messageManager->handle($rabbitMessage);
            $this->channel->basic_ack($rabbitMessage->delivery_info['delivery_tag']);
        };
        return $this->channel->basic_consume($this->adminQueue, '', false, false, false, false, $callbackMng);
    }

    public function createMessage(AbstractMessage $message, $update, $q)
    {
        $msg = new AMQPMessage($message->create($update));
        $this->channel->basic_publish($msg, '', $q);
    }

    public function run()
    {

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
            
        }
    }
}
