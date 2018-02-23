<?php


namespace Rdlv\JDanger;

use DateTime;

class Flow
{
    /** @var array */
    private $sessions;

    /** @var int */
    private $duration;

    /** @var DateTime */
    private $time;
    
    /** @var float */
    private $offset;
    
    /** @var int */
    private $index;
    
    public function __construct($sessions, $time = null)
    {
        if ($sessions) {
            if ($time === null) {
                // week-begin by default
                $time = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    date('Y-m-d', strtotime('-'. (date('w')-1) .' days')) .' 00:00:00'
                );
            }
            
            $this->sessions = $sessions;
            
            $this->duration = array_reduce($sessions, function ($carry, $session) {
                return $carry + $session->duration;
            }, 0);

            $this->setTime($time);
        }
    }
    
    public function setTime($time)
    {
        if (!$time instanceof DateTime) {
            $this->time = DateTime::createFromFormat('U', $time);
        }
        else {
            $this->time = clone $time;
        }

        $last = $this->sessions[0];

        // time since last publication
        $delta = $this->time->format('U') - $last->date->format('U');

        // current time in concatenated transmissions
        $this->offset = $delta % $this->duration;
        $this->index = 0;
        while ($this->offset >= $this->sessions[$this->index]->duration) {
            $this->offset -= $this->sessions[$this->index]->duration;
            ++$this->index;

            if ($this->index >= count($this->sessions)) {
                $this->index = 0;
            }
        }
    }

    /**
     * @return int Index of current session
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return float Play offset of current session
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return DateTime Time position in the flow
     */
    public function getTime()
    {
        return $this->time;
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

    /**
     * @return array the array representation of the object or null
     * @since 5.1.0
     */
    public function toArray()
    {
        $dateFormat = 'Y-m-d H:i:s';
        $output = get_object_vars($this);
        foreach ($output as $key => $value) {
            if ($key === 'sessions') {
                foreach ($output[$key] as $index => $session) {
                    $output[$key][$index] = get_object_vars($session);
                    
                    foreach ($output[$key][$index] as $skey => $svalue) {
                        if ($svalue instanceof DateTime) {
                            $output[$key][$index][$skey] = $svalue->format($dateFormat);
                        }
                    }
                }
            }
            elseif ($value instanceof DateTime) {
                $output[$key] = $value->format($dateFormat);
            }
        }
        return $output;
    }
}