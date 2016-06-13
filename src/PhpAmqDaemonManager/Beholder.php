<?php

namespace PhpAmqDaemonManager;

use PhpAmqDaemonManager\Message\AbstractMessage;
use PhpAmqDaemonManager\Message\AgentStatusUpdate;
use PhpAmqDaemonManager\Message\BeholderAdminStatus;
use PhpAmqDaemonManager\Message\BeholderStatusGet;
use PhpAmqDaemonManager\Message\BeholderStatusUpdate;
use PhpAmqDaemonManager\Message\SimpleMessage;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Beholder
{
    public $minions = [];
    public $messageManager;
    protected $fn;

    protected function initMinion($host, $role, $queue = null)
    {
        if (!isset($this->minions[$host])) {
            $this->minions[$host] = [];
        }
        if (!isset($this->minions[$host][$role])) {
            $this->minions[$host][$role] = ['status' => 1];
        }
        if ($queue) {
            $this->minions[$host][$role]['queue'] = $queue;
        }
    }

    protected function changeMinionStatus($host, $role, $status)
    {
        if ($this->minions[$host][$role]['status'] !== $status) {
            $this->minions[$host][$role]['status'] = $status;
            $this->sendUpdateStatusToMinion($role, $status);
        }
    }

    protected function sendUpdateStatusToMinion($host, $role)
    {
        $status = $this->minions[$host][$role]['queue'];
        if ($q = $this->minions[$host][$role]['queue']) {
            $this->createMessage(new AgentStatusUpdate(), ['role' => $role, 'status' => $status], $q);
        }
        if ($fn = $this->fn) {
            $fn($host, $role, $status);
        }
    }

    public function __construct(AbstractConnection $connection, $adminQueue)
    {
        $this->connection = $connection;
        $this->channel = $this->connection->channel();
        $this->adminQueue = $adminQueue;
        $this->messageManager = new MessageManager();
        
        $this->messageManager->bind(new BeholderStatusUpdate(function (MQMessage $m) {
            $this->initMinion($m->get('hostname'), $m->get('role'), $m->get('queue'));
            if ($this->minions[$m->get('hostname')][$m->get('role')]['status'] !== $m->get('status')) {
                $this->sendUpdateStatusToMinion($m->get('hostname'), $m->get('role'));
            }
        }));

        $this->messageManager->bind(new BeholderAdminStatus(function (MQMessage $m) {
            $this->initMinion($m->get('hostname'), $m->get('role'));
            $this->changeMinionStatus($m->get('hostname'), $m->get('role'), $m->get('status'));
        }));

        $this->messageManager->bind(new BeholderStatusGet(function (MQMessage $message) {
            $q = $message->get('queue');
            $this->createMessage(new SimpleMessage(), ['text' => $this->minions], $q);
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

    public function addHandler($fn)
    {
        $this->fn = $fn;
    }

    public function run()
    {
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }
}
