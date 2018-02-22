<?php


namespace JDanger;


use DateInterval;
use DateTime;

class Programme
{
    /** @var array */
    private $sessions;

    /** @var int */
    private $duration;

    /** @var DateTime */
    private $start;
    
    /** @var float */
    private $offset;
    
    /** @var int */
    private $index;
    
    public function __construct($sessions, $start = null)
    {
        if ($sessions) {
            if ($start === null) {
                // week-begin by default
                $start = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    date('Y-m-d', strtotime('-'. (date('w')-1) .' days')) .' 00:00:00'
                );
            }
            
            $this->sessions = $sessions;
            
            // normalize durations
            foreach ($this->sessions as &$session) {
                $session->duration = (int)$session->duration;
                $session->meta = unserialize($session->meta);
                $session->playtime = $session->meta['playtime'];
            }
            
            $this->duration = array_reduce($sessions, function ($carry, $item) {
                return $carry + $item->duration;
            }, 0);

            $this->setStart($start);
        }
    }
    
    public function setStart($start)
    {
        if (!$start instanceof DateTime) {
            $this->start = DateTime::createFromFormat('U', $start);
        }
        else {
            $this->start = clone $start;
        }

        $last = $this->sessions[0];

        // time since last publication
        $delta = $this->start->format('U') - DateTime::createFromFormat('Y-m-d H:i:s', $last->date)->format('U');

        // current time in concatenated transmissions
        $this->offset = $delta % $this->duration;
        $this->index = 0;
        while ($this->offset >= $this->sessions[$this->index]->duration) {
            $this->offset -= $this->sessions[$this->index]->duration;
            ++$this->index;

            // should not occur since we use modulo to initialize offset
            if ($this->index >= count($this->sessions)) {
                $this->index = 0;
            }
        }
    }
    
    public function getIndex()
    {
        return $this->index;
    }
    
    public function getOffset()
    {
        return $this->offset;
    }
    
    public function getStart()
    {
        return $this->start;
    }
    
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @param $index
     * @return object
     */
    public function getSession($index)
    {
        return $this->sessions[$index % count($this->sessions)];
    }
}