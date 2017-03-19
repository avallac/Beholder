<?php

namespace Beholder\Message;

class BeholderStatusUpdate extends AbstractCallBackMessage
{
    protected $callBack;
    protected $require = ['status', 'role', 'hostname', 'queue', 'pid', 'infoBlock'];

    public function getCommand()
    {
        return 'beholder.status.update';
    }
}