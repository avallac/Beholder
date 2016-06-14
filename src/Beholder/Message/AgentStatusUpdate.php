<?php

namespace Beholder\Message;

class AgentStatusUpdate extends AbstractCallBackMessage
{
    protected $callBack;
    protected $require = ['status', 'role'];

    public function getCommand()
    {
        return 'agent.status.update';
    }
}
