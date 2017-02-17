<?php

namespace Beholder\Message;

class BeholderAdminStatus extends AbstractCallBackMessage
{
    protected $callBack;
    protected $require = ['hostname', 'pid', 'role', 'status'];

    public function getCommand()
    {
        return 'beholder.admin.status';
    }
}