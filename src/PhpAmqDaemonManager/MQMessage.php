<?php

namespace PhpAmqDaemonManager;

use PhpAmqDaemonManager\Exception\CantFindFieldInMessageViolationException;

class MQMessage
{
    private $data;

    public function __construct($param)
    {
        if (is_array($param)) {
            $this->data = $param;
        } else {
            $this->data = json_decode($param, true);
        }
    }

    public function __toString()
    {
        return (string)json_encode($this->data);
    }

    public function get($name)
    {
        if ($this->exists($name)) {
            return $this->data[$name];
        } else {
            throw new CantFindFieldInMessageViolationException($name);
        }
    }

    public function exists($name)
    {
        if (array_key_exists($name, $this->data)) {
            return true;
        } else {
            return false;
        }
    }
}