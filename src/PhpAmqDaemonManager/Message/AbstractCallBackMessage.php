<?php

namespace PhpAmqDaemonManager\Message;

use PhpAmqDaemonManager\MQMessage;

abstract class AbstractCallBackMessage extends AbstractMessage
{
    protected $callBack = '';

    public function __construct($fn = '')
    {
        $this->callBack = $fn;
    }

    public function handle(MQMessage $message)
    {
        $fn = $this->callBack;
        if ($fn) {
            $fn($message);
        }
    }
}