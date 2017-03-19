<?php

namespace Beholder;

use Beholder\Message\AbstractMessage;
use Beholder\Exception\CantFindMessageViolationException;
use PhpAmqpLib\Message\AMQPMessage;

class MessageManager
{
    private $messages = [];

    public function bind(AbstractMessage $message)
    {
        $this->messages[$message->register()] = $message;
    }

    public function handle(AMQPMessage $message, $client)
    {
        $msg = new MQMessage($message->body);
        return $this->getMessage($msg->get('command'))->handle($msg, $client);
    }

    public function getMessageKeys()
    {
        return array_keys($this->messages);
    }

    /**
     * @param $key
     * @return AbstractMessage|null
     */
    public function getMessage($key)
    {
        if ($this->messageIsAllow($key)) {
            return $this->messages[$key];
        } else {
            throw new CantFindMessageViolationException($key);
        }

    }

    public function messageIsAllow($key)
    {
        if (isset($this->messages[$key])) {
            return true;
        } else {
            return false;
        }
    }

    public function requiredFields($key)
    {
        return $this->getMessage($key)->requiredFields();
    }
}
