<?php

namespace Beholder;

use Beholder\Exception\DuplicatePIDException;
use Beholder\Message\AbstractMessage;
use Beholder\Message\AdminMinionStatus;
use Beholder\Message\AgentStatusUpdate;
use Beholder\Message\BeholderAdminKill;
use Beholder\Message\BeholderAdminSetLimit;
use Beholder\Message\BeholderStatusGet;
use Beholder\Message\BeholderStatusUpdate;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Beholder
{
    const TIMETOLIVE = 600;
    const TIMEUPDATE = 60;
    const SIG_DUP = -1;
    const SIG_KILL = -2;
    public $minions = [];
    public $minionStatistic = [];
    public $messageManager;

    protected function sendUpdateStatusToMinion(MinionStatus $minionRecord)
    {
        $status =$minionRecord->getTargetStatus();
        if ($queue = $minionRecord->getQueue()) {
            $role = $minionRecord->getRole();
            $this->createMessage(new AgentStatusUpdate(), ['role' => $role, 'status' => $status], $queue);
        }
    }
    protected function sendHaltToMinion($role, $queue, $code = -1)
    {
        $this->createMessage(new AgentStatusUpdate(), ['status' => $code, 'role' => $role], $queue);
    }

    protected function syncStatus()
    {
        /** @var MinionStatus $minionRecord */
        foreach ($this->minionStorage->getAll() as $minionRecord) {
            if ($minionRecord->getTargetStatus() !== $minionRecord->getStatus()) {
                $this->sendUpdateStatusToMinion($minionRecord);
            }
        }
    }

    public function __construct(AbstractConnection $connection, $adminQueue)
    {
        $this->connection = $connection;
        $this->channel = $this->connection->channel();
        $this->adminQueue = $adminQueue;
        $this->messageManager = new MessageManager();
        $this->minionStorage = new MinionStorage();

        $this->messageManager->bind(new BeholderStatusUpdate(function (MQMessage $m) {
            try {
                $minionRecord = $this->minionStorage->searchOrCreate($m);
                $minionRecord->setStatus($m->get('status'));
                $minionRecord->update();
                $this->minionStorage->update();
                $this->syncStatus();
            } catch (DuplicatePIDException $exception) {
                $this->sendHaltToMinion($m->get('role'), $m->get('queue'));
            }
        }));

        $this->messageManager->bind(new BeholderAdminSetLimit(function (MQMessage $m) {
            $this->minionStorage->setLimit($m);
            $this->minionStorage->update();
            $this->syncStatus();
        }));

        $this->messageManager->bind(new BeholderAdminKill(function (MQMessage $m) {
            if ($minionRecord = $this->minionStorage->search($m)) {
                $this->sendHaltToMinion($minionRecord->getRole(), $minionRecord->getQueue(), -2);
            }
            $this->minionStorage->update();
            $this->syncStatus();
        }));

        $this->messageManager->bind(new BeholderStatusGet(function (MQMessage $message) {
            $q = $message->get('queue');
            $minions =  $this->minionStorage->getAll();
            $this->createMessage(new AdminMinionStatus(), ['minions' => $minions], $q);
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
