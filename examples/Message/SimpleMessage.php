<?php

namespace PhpAmqDaemonManager\Message;

class SimpleMessage extends AbstractCallBackMessage
{
    protected $callBack;
    protected $require = ['text'];

    public function getCommand()
    {
        return 'simple';
    }
}