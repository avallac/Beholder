<?php

namespace Beholder\Message;

class BeholderAdminSetLimit extends AbstractCallBackMessage
{
    protected $callBack;
    protected $require = ['host', 'role', 'limit'];

    public function getCommand()
    {
        return 'beholder.admin.limit';
    }
}