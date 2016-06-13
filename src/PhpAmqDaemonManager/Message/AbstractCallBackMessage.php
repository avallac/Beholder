<?php

namespace PhpAmqDaemonManager\Message;

use PhpAmqDaemonManager\Exception\CantFindMessageViolationException;
use PhpAmqDaemonManager\MQMessage;

abstract class AbstractCallBackMessage extends AbstractMessage
{
    protected $callBack;

    public function __construct($fn = null)
    {
        if ($fn) {
            $this->callBack = $fn;
        } else {
            $this->callBack = function ($message) {
                throw new CantFindMessageViolationException();
            };
        }
    }

    public function handle(MQMessage $message)
    {
        $fn = $this->callBack;
        if ($fn) {
            $fn($message);
        }
    }
}