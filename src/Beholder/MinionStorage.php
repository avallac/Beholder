<?php

namespace Beholder;

use Beholder\Exception\DuplicatePIDException;

class MinionStorage
{

    protected $minions;
    protected $control;

    public function __construct()
    {

    }

    public function setLimit(MQMessage $m)
    {
        $host = $m->get('hostname');
        $role = $m->get('role');
        $limit = $m->get('limit');
        $this->control[$host][$role]['limit'] = $limit;
    }

    /**
     * @param MQMessage $m
     * @return MinionStatus
     */
    public function search(MQMessage $m)
    {
        $host = $m->get('hostname');
        $role = $m->get('role');
        $pid = $m->get('pid');
        if (isset($this->minions[$host][$role][$pid])) {
            return $this->minions[$host][$role][$pid];
        } else {
            return null;
        }
    }

    /**
     * @param MQMessage $m
     * @return MinionStatus
     */
    public function searchOrCreate(MQMessage $m)
    {
        $host = $m->get('hostname');
        $role = $m->get('role');
        $pid = $m->get('pid');
        $queue = $m->get('queue');
        if (!$this->search($m)) {
            $this->minions[$host][$role][$pid] = new MinionStatus($host, $role, $pid, $queue);
        } else {
            $currentQ = $this->minions[$host][$role][$pid]->getQueue();
            if ($queue !== $currentQ) {
                throw new DuplicatePIDException();
            }
        }
        if (!isset($this->control[$host][$role]['limit'])) {
            $this->control[$host][$role]['limit'] = -1;
        }
        return $this->minions[$host][$role][$pid];
    }

    public function update()
    {
        foreach (array_keys($this->minions) as $host) {
            foreach (array_keys($this->minions[$host]) as $role) {
                $limit = $this->control[$host][$role]['limit'];
                $limit = ($limit >= 0) ? $limit : 1000;
                foreach (array_keys($this->minions[$host][$role]) as $pid) {
                    /** @var MinionStatus $minion */
                    $minion = $this->minions[$host][$role][$pid];
                    if ($minion->isDown()) {
                        unset($this->minions[$host][$role][$pid]);
                    } else {
                        if ($minion->getTargetStatus() === MinionStatus::ACTIVE) {
                            $limit--;
                            if ($limit < 0) {
                                $minion->setTargetStatus(MinionStatus::OFF);
                            }
                        }
                    }
                }
                foreach (array_keys($this->minions[$host][$role]) as $pid) {
                    $minion = $this->minions[$host][$role][$pid];
                    if ($limit > 0) {
                        if ($minion->getTargetStatus() !== MinionStatus::ACTIVE) {
                            $minion->setTargetStatus(MinionStatus::ACTIVE);
                            $limit--;
                        }
                    }
                }
                if (empty($this->minions[$host][$role])) {
                    unset($this->minions[$host][$role]);
                }
            }
            if (empty($this->minions[$host])) {
                unset($this->minions[$host]);
            }
        }
    }

    public function getAll()
    {
        $return = [];
        foreach (array_keys($this->minions) as $host) {
            foreach (array_keys($this->minions[$host]) as $role) {
                foreach (array_keys($this->minions[$host][$role]) as $pid) {
                    $return [] = $this->minions[$host][$role][$pid];
                }
            }
        }
        return $return;
    }
}