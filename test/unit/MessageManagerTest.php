<?php

namespace Beholder;

class MessageManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  MessageManager */
    private $manager;

    public function setUp()
    {
        $this->manager = new MessageManager();
        $message = \Mockery::mock('\Beholder\Message\AbstractMessage');
        $message->shouldReceive('handle')->andReturn('test string');
        $message->shouldReceive('register')->andReturn('test.test');
        $message->shouldReceive('requiredFields')->andReturn(['testField']);
        $this->manager->bind($message);
    }

    public function testHandle()
    {
        $message = \Mockery::mock('\PhpAmqpLib\Message\AMQPMessage');
        $message->body = new MQMessage(['command' => 'test.test']);
        $return = $this->manager->handle($message);
        $this->assertSame('test string', $return);
    }

    /**
     * @expectedException \Beholder\Exception\CantFindMessageViolationException
     */
    public function testHandleException()
    {
        $message = \Mockery::mock('\PhpAmqpLib\Message\AMQPMessage');
        $message->body = new MQMessage(['command' => 'bad.test']);
        $return = $this->manager->handle($message);
        $this->assertSame('test string', $return);
    }


    public function testGetMessageOk()
    {
        $this->assertInstanceOf(
            '\Beholder\Message\AbstractMessage',
            $this->manager->getMessage('test.test')
        );
    }

    /**
     * @expectedException \Beholder\Exception\CantFindMessageViolationException
     */
    public function testGetMessageBad()
    {
        $this->assertSame(null, $this->manager->getMessage('bad.test'));
    }

    public function testRequiredFieldsOk()
    {
        $this->assertSame(['testField'], $this->manager->requiredFields('test.test'));
    }

    /**
     * @expectedException \Beholder\Exception\CantFindMessageViolationException
     */
    public function testRequiredFieldsBad()
    {
        $this->assertSame(null, $this->manager->requiredFields('bad.test'));
    }

    public function testGetMessageKeys()
    {
        $this->assertSame(['test.test'], $this->manager->getMessageKeys());
    }
}
