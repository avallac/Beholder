<?php

namespace PhpAmqDaemonManager;

use \PhpAmqpLib\Channel\AMQPChannel;
use \PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Agent
{
    protected $myQueueName;
    protected $hostname;
    protected $adminQueue;
    /** @var AMQPChannel channel */
    protected $channel;
    protected $roles = [];
    /** @var AbstractConnection AbstractConnection */
    protected $connection;
    protected $roundTimer;

    public function __construct(AbstractConnection $connection, $hostname, $adminQueue, $roundTimer = 60)
    {
        $this->connection = $connection;
        $this->channel = $this->connection->channel();
        list($this->myQueueName, ,) = $this->channel->queue_declare("");
        $this->hostname = $hostname;
        $this->adminQueue = $adminQueue;
        $this->roundTimer = $roundTimer;
        $this->initManagementConsume();
    }

    protected function basicConsume($queue, $fn)
    {
        return $this->channel->basic_consume($queue, '', false, false, false, false, $fn);
    }

    protected function initManagementConsume()
    {
        $callbackMng = function ($rabbitMessage) {
            $message = json_decode($rabbitMessage->body, true);
            $role = $message['role'];
            if (isset($this->roles[$role])) {
                if ($this->roles[$role]['status'] !== $message['status']) {
                    if ($message['status'] == 1) {
                        $workQueue = $this->roles[$role]['workQueue'];
                        $fnUserCallBack = $this->roles[$role]['userCallBack'];
                        $this->roles[$role]['consumeTag'] = $this->basicConsume($workQueue, $fnUserCallBack);
                    } else {
                        $this->channel->basic_cancel($this->roles[$role]['consumeTag']);
                        $this->roles[$role]['consumeTag'] = null;
                    }
                    $this->roles[$role]['status'] = $message['status'];
                }
                $this->roles[$role]['lastUpdated'] = 0;
            }
            $this->channel->basic_ack($rabbitMessage->delivery_info['delivery_tag']);
        };
        return $this->basicConsume($this->adminQueue, $callbackMng);
    }

    public function bindRole($roleName, $workQueue, $fnUserCallBack)
    {
        $this->roles[$roleName] = [
            'status' => 0,
            'workQueue' => $workQueue,
            'userCallBack' => $fnUserCallBack,
            'consumeTag' => null,
            'lastUpdated' => 0
        ];
    }

    public function getStatusRole($roleName)
    {
        return $this->roles[$roleName];
    }

    public function sendReport()
    {
        $now = microtime(true);
        foreach ($this->roles as $roleName => $roleInfo) {
            if ($now - $roleInfo['lastUpdated'] > $this->roundTimer) {
                $msg = new AMQPMessage(json_encode([
                    'hostname'=> $this->hostname,
                    'role' => $roleName,
                    'status' => $roleInfo['status']
                ]));
                $this->channel->basic_publish($msg, '', $this->adminQueue);
                $this->roles[$roleName]['lastUpdate'] = $now;
            }
        }
    }

    public function run()
    {
        $this->sendReport();
        while (count($this->channel->callbacks)) {
            $read = array($this->connection);
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
