<?php

namespace PhpAmqDaemonManager;

class GeneralTest extends \PHPUnit_Framework_TestCase
{
    public function testT()
    {
        $socket = fopen('/dev/zero', 'r');
        $channel = \Mockery::mock('\PhpAmqpLib\Channel\AMQPChannel');
        $connection = \Mockery::mock('\PhpAmqpLib\Connection\AbstractConnection');
        $connection->shouldReceive('getSocket')->andReturn($socket);
        $connection->shouldReceive('channel')->andReturn($channel);
        $channel->shouldReceive('queue_declare')->andReturn(['minionQ']);
        $channel->shouldReceive('basic_consume');

        $channel->shouldReceive('basic_publish')->with(
            \Mockery::on(function ($m) use (&$beholder, &$agent) {
                if (preg_match('/^beholder\./', $m->body->get('command'))) {
                    $beholder->messageManager->handle($m);
                    return true;
                }
            }),
            '',
            'testManagementQ'
        );

        $channel->shouldReceive('basic_publish')->with(
            \Mockery::on(function ($m) use (&$beholder, &$agent) {
                if (preg_match('/^agent\./', $m->body->get('command'))) {
                    $agent->getMessageManager()->handle($m);
                    return true;
                }
            }),
            '',
            'minionQ'
        );

        $beholder = new Beholder($connection, 'testManagementQ');
        $agent = new Minion($connection, 'testName', 'testManagementQ');
        $agent->bindRole('integrationTest', 'testQ', function () {

        });
        $this->assertEquals(0, $agent->getRole('integrationTest')['status']);
        $agent->run();
        $this->assertEquals(1, $agent->getRole('integrationTest')['status']);
    }
}