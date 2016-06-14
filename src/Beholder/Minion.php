<?php

namespace Beholder;

use Beholder\Message\AbstractMessage;
use Beholder\Message\AgentStatusUpdate;
use Beholder\Message\BeholderStatusUpdate;
use \PhpAmqpLib\Channel\AMQPChannel;
use \PhpAmqpLib\Connection\AbstractConnection;
use \PhpAmqpLib\Message\AMQPMessage;

class Minion
{
    protected $myQueueName;
    protected $hostname;
    protected $adminQueue;
    /** @var AMQPChannel channel */
    protected $channel;
    protected $roles = [];
    /** @var AbstractConnection  */
    protected $connection;
    protected $roundTimer;
    /** @var MessageManager */
    protected $messageManager;

    public function __construct(AbstractConnection $connection, $hostname, $adminQueue, $roundTimer = 60)
    {
        $this->connection = $connection;
        $this->channel = $this->connection->channel();
        $this->channel->basic_qos(0, 1, true);
        list($this->myQueueName, ,) = $this->channel->queue_declare("");
        $this->hostname = $hostname;
        $this->adminQueue = $adminQueue;
        $this->roundTimer = $roundTimer;
        $this->messageManager = new MessageManager();
        $this->initManagementConsume();
    }

    protected function basicConsume($queue, $fn)
    {
        return $this->channel->basic_consume($queue, '', false, false, false, false, $fn);
    }

    protected function initManagementConsume()
    {

        $this->messageManager->bind(new AgentStatusUpdate(function (MQMessage $message) {
            $role = $message->get('role');
            if (isset($this->roles[$role])) {
                if ($this->roles[$role]['status'] !== $message->get('status')) {
                    if ($message->get('status') == 1) {
                        $fnCallback = function ($rabbitMessage) use ($role) {
                            $this->roles[$role]['messageManager']->handle($rabbitMessage);
                            $this->channel->basic_ack($rabbitMessage->delivery_info['delivery_tag']);
                        };
                        $workQueue = $this->roles[$role]['workQueue'];
                        $this->roles[$role]['consumeTag'] = $this->basicConsume($workQueue, $fnCallback);
                    } else {
                        $this->channel->basic_cancel($this->roles[$role]['consumeTag']);
                        $this->roles[$role]['consumeTag'] = null;
                    }
                    $this->roles[$role]['status'] = $message->get('status');
                    $this->roles[$role]['lastUpdated'] = 0;
                }
            }
        }));
        $fnCallback = function ($rabbitMessage) {
            $this->messageManager->handle($rabbitMessage);
            $this->channel->basic_ack($rabbitMessage->delivery_info['delivery_tag']);
        };
        $this->basicConsume($this->myQueueName, $fnCallback);
    }

    public function bindRole($roleName, $workQueue)
    {
        $this->roles[$roleName] = [
            'status' => 0,
            'workQueue' => $workQueue,
            'messageManager' => new MessageManager(),
            'consumeTag' => null,
            'lastUpdated' => 0
        ];
    }

    public function setMessageManager($roleName, $manager)
    {
        $this->roles[$roleName]['messageManager'] = $manager;
    }

    public function getMessageManager()
    {
        return $this->messageManager;
    }

    public function getRole($roleName)
    {
        return $this->roles[$roleName];
    }

    public function sendReport()
    {
        $now = microtime(true);
        foreach (array_keys($this->roles) as $roleName) {
            if ($now - $this->roles[$roleName]['lastUpdated'] > $this->roundTimer) {
                $update = [
                    'hostname'=> $this->hostname,
                    'role' => $roleName,
                    'status' => $this->roles[$roleName]['status'],
                    'queue' => $this->myQueueName,
                ];
                $this->roles[$roleName]['lastUpdated'] = $now;
                $this->createMessage(new BeholderStatusUpdate(), $update);
            }
        }
    }

    public function createMessage(AbstractMessage $message, $update)
    {
        $msg = new AMQPMessage($message->create($update));
        $this->channel->basic_publish($msg, '', $this->adminQueue);
    }

    public function run()
    {
        $this->sendReport();
        $socket = $this->connection->getSocket();
        while (count($this->channel->callbacks)) {
            $read = [$socket];
            $write = null;
            $except = null;
            $changeStreamsCount = stream_select($read, $write, $except, $this->roundTimer);
            if ($changeStreamsCount === false) {
                throw new \RuntimeException();
            } elseif ($changeStreamsCount > 0) {
                $this->channel->wait();
            }
            $this->sendReport();
        }
    }
}
