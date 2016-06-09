<?php

namespace PhpAmqDaemonManager\Message;

class BeholderStatusUpdate extends AbstractCallBackMessage
{
    protected $callBack;
    protected $require = ['status', 'role', 'hostname', 'queue'];

    public function getCommand()
    {
        return 'beholder.status.update';
    }
}