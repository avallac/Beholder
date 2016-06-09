<?php

namespace PhpAmqDaemonManager;

class AgentTest extends \PHPUnit_Framework_TestCase
{
    
    protected function getManagementCallBack()
    {
        $managementCallBack = null;
        $channel = \Mockery::mock('\PhpAmqpLib\Channel\AMQPChannel');
        $channel->shouldReceive('queue_declare')->with("")->andReturn(['qName'])->once();
        $channel->shouldReceive('basic_consume')->with(
            "qName",
            "",
            false,
            false,
            false,
            false,
            \Mockery::on(function ($callBack) use (&$managementCallBack) {
                $managementCallBack = $callBack;
                return true;
            })
        );
        $connection = \Mockery::mock('\PhpAmqpLib\Connection\AbstractConnection');
        $connection->shouldReceive('channel')->andReturn($channel);
        $agent = new Agent($connection, 'testHostName', 'adminQ');
        return [$agent, $managementCallBack, $channel];
    }

    public function testInitManagementConsume()
    {
        list($agent, $func, $channel) = $this->getManagementCallBack();
        $agent->bindRole('testRole', 'testQ', 'userCallBack');
        $status = $agent->getStatusRole('testRole');
        $this->assertSame([
            'status' => 0,
            'workQueue' => 'testQ',
            'userCallBack' => 'userCallBack',
            'consumeTag' => null,
            'lastUpdated' => 0
        ], $status);
    }

    private function updateClass($exemplar, $param, $value)
    {
        $myClassReflection = new \ReflectionClass(get_class($exemplar));
        $ref = $myClassReflection->getProperty($param);
        $ref->setAccessible(true);
        $ref->setValue($exemplar, $value);
    }

    public function statusProvider()
    {
        return [
            [
                [
                    'testRole'=> [
                        'status' => 0,
                        'consumeTag' => null,
                        'workQueue' => 'testQ',
                        'userCallBack' => 'userCallBack'
                    ]
                ],
                [
                    'testRole'=> [
                        'status' => 1,
                        'consumeTag' => 234,
                        'workQueue' => 'testQ',
                        'userCallBack' => 'userCallBack',
                        'lastUpdated' => 0
                    ]
                ],
                1,
                [ 'message' => 345, 'consume' => 234 ]
            ], [
                [
                    'testRole'=> [
                        'status' => 1,
                        'consumeTag' => 235,
                        'workQueue' => 'testQ',
                        'userCallBack' => 'userCallBack'
                    ]
                ],
                [
                    'testRole'=> [
                        'status' => 0,
                        'consumeTag' => null,
                        'workQueue' => 'testQ',
                        'userCallBack' => 'userCallBack',
                        'lastUpdated' => 0
                    ]
                ],
                0,
                [ 'message' => 346, 'consume' => 235 ]
            ]
        ];
    }

    /**
     * @dataProvider statusProvider
     */
    public function testMessageInManagementQ($roleStatus, $newStatus, $newStatusId, $tags)
    {
        list($agent, $func, $channel) = $this->getManagementCallBack();
        $this->updateClass($agent, 'roles', $roleStatus);
        if (!$newStatusId) {
            $channel->shouldReceive('basic_cancel')->with($tags['consume'])->once();
        } else {
            $channel->shouldReceive('basic_consume')
                ->with('testQ', '', false, false, false, false, 'userCallBack')
                ->once()->andReturn($tags['consume']);
        }
        $channel->shouldReceive('basic_ack')->with($tags['message'])->once($tags['message']);
        $message = \Mockery::mock('\PhpAmqpLib\Message\AMQPMessage');
        $message->body = (string)new MQMessage([
            'status' => $newStatusId,
            'role' => 'testRole',
            'command' => 'agent.status.update'
        ]);
        $message->delivery_info = ['delivery_tag' => $tags['message']];
        $func($message);
        $this->assertSame($newStatus['testRole'], $agent->getStatusRole('testRole'));
    }

    public function testDontSendReport()
    {
        list($agent, $func, $channel) = $this->getManagementCallBack();
        $this->updateClass($agent, 'roles', [
            'testRole' => [
                'status' => 0,
                'lastUpdated' => microtime(true)
            ]
        ]);
        $agent->sendReport();
    }

    public function testSendReport()
    {
        list($agent, $func, $channel) = $this->getManagementCallBack();
        $channel->shouldReceive('basic_publish')->with(
            \Mockery::on(function ($msg) {
                return 1 === $msg->body->get('status');
            }),
            '',
            'adminQ'
        )->once();
        $this->updateClass($agent, 'roles', [
            'testRole' => [
                'status' => 1,
                'lastUpdated' => 0
            ]
        ]);
        $agent->sendReport();
    }

    public function tearDown()
    {
        \Mockery::close();
    }
}
