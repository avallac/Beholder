<?php

namespace PhpAmqDaemonManager;

class AbstractMessageTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new \Mockery\Container;
    }

    public function setProtectedProperty($object, $property, $value)
    {
        $reflection = new \ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($object, $value);
    }

    public function testGetRequiredFields()
    {
        $mock = $this->container->mock('\PhpAmqDaemonManager\Message\AbstractMessage')->shouldDeferMissing();
        $this->setProtectedProperty($mock, 'require', [1,3,4]);
        $this->assertEquals([1,3,4], $mock->getRequiredFields());
    }

    /**
     * @expectedException \PhpAmqDaemonManager\Exception\NotValidMessageViolationException
     */
    public function testRequiredFields()
    {
        $mock = $this->container->mock('\PhpAmqDaemonManager\Message\AbstractMessage')->shouldDeferMissing();
        $mock->shouldReceive('getCommand')->andReturn('test.command');
        $this->setProtectedProperty($mock, 'require', ['testField']);
        $mock->validate(new MQMessage(['t' => 1]));
    }
}