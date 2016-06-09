<?php

namespace PhpAmqDaemonManager;

class MQMessageTest extends \PHPUnit_Framework_TestCase
{
    public function testInputArray()
    {
        $m = new MQMessage(['p' => 1]);
        $this->assertSame($m->get('p'), 1);
    }

    public function testInputJson()
    {
        $m = new MQMessage(json_encode(['p' => 1]));
        $this->assertSame($m->get('p'), 1);
    }

    public function testOutJson()
    {
        $m = new MQMessage(['p' => 1]);
        $this->assertSame((string)$m, '{"p":1}');
    }

    public function testGetOk()
    {
        $m = new MQMessage(['p' => 1]);
        $this->assertSame($m->get('p'), 1);
    }

    /**
     * @expectedException \PhpAmqDaemonManager\Exception\CantFindFieldInMessageViolationException
     */
    public function testGetBad()
    {
        $m = new MQMessage(['p' => 1]);
        $this->assertSame($m->get('d'), null);
    }
}
