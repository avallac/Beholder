<?php

namespace Beholder\Message;

class AdminMinionStatus extends AbstractCallBackMessage
{
    protected $callBack;
    protected $require = ['minions'];

    public function getCommand()
    {
        return 'admin.minion.status';
    }
}