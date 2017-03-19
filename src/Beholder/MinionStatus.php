<?php

namespace Beholder;

class MinionStatus implements \JsonSerializable
{
    const TIMETOLIVE = 600;
    const ACTIVE = 1;
    const OFF = 0;
    const HALTED = -1;
    protected $status;
    protected $queue;
    protected $pid;
    protected $role;
    protected $host;
    protected $targetStatus;
    protected $lastUpdate;
    protected $infoBlock;

    public function __construct($host, $role, $pid, $queue)
    {
        $this->host = $host;
        $this->role = $role;
        $this->pid = $pid;
        $this->queue = $queue;
        $this->status = self::OFF;
    }

    public function setInfoBlock($infoBlock)
    {
        return $this->infoBlock = $infoBlock;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getTargetStatus()
    {
        return $this->targetStatus;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setTargetStatus($targetStatus)
    {
        $this->targetStatus = $targetStatus;
    }

    public function update()
    {
        $this->lastUpdate = microtime(true);
    }

    public function isDown()
    {
        if ($this->status === self::HALTED) {
            return true;
        }
        $currentTime = microtime(true);
        if (($this->lastUpdate + Beholder::TIMETOLIVE) < $currentTime) {
            return true;
        } else {
            return false;
        }
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
