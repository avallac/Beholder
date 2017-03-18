<?php

namespace Beholder\Message;

class BeholderAdminStatus extends AbstractCallBackMessage
{
    protected $callBack;
    protected $require = ['host', 'role', 'limit'];

    public function getCommand()
    {
        return 'beholder.admin.status';
    }
}