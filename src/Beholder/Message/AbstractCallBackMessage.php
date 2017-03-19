<?php

namespace Beholder\Message;

use Beholder\Exception\CantFindMessageViolationException;
use Beholder\Minion;
use Beholder\MQMessage;

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

    public function handle(MQMessage $message, Minion $minion)
    {
        $fn = $this->callBack;
        if ($fn) {
            $fn($message);
        }
    }
}