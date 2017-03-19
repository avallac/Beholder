<?php

namespace Beholder\Message;

class BeholderAdminKill extends AbstractCallBackMessage
{
    protected $callBack;
    protected $require = ['role', 'hostname', 'pid'];

    public function getCommand()
    {
        return 'beholder.admin.kill';
    }
}