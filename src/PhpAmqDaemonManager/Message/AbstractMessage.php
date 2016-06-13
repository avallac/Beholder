<?php

namespace PhpAmqDaemonManager\Message;

use PhpAmqDaemonManager\Exception\NotValidMessageViolationException;
use PhpAmqDaemonManager\MQMessage;

abstract class AbstractMessage
{
    protected $require = [];

    public function register()
    {
        return $this->getCommand();
    }

    public function validate(MQMessage $message)
    {
        foreach ($this->require as $field) {
            if (!$message->exists($field)) {
                throw new NotValidMessageViolationException($this->getCommand().": can't find ".'"'.$field.'"');
            }
        }
        return true;
    }

    public function getRequiredFields()
    {
        return $this->require;
    }

    public function create($params)
    {
        $arr = ['command' => $this->getCommand()];
        foreach ($this->require as $item) {
            if (!array_key_exists($item, $params)) {
                throw new NotValidMessageViolationException($this->getCommand().": can't find ".'"'.$item.'"');
            }
            $arr[$item] = $params[$item];
        }
        $outM = new MQMessage($arr);
        $this->validate($outM);
        return $outM;
    }

    abstract public function getCommand();
    abstract public function handle(MQMessage $message);
}
