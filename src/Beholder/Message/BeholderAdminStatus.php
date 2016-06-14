<?php

namespace Beholder\Message;

class BeholderAdminStatus extends AbstractCallBackMessage
{
    protected $callBack;
    protected $require = ['hostname', 'role', 'status'];

    public function getCommand()
    {
        return 'beholder.admin.status';
    }
}