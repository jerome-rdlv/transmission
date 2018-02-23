<?php


namespace Rdlv\JDanger;

use DateInterval;
use DateTime;

class Flow
{
    const PLACEHOLDER_ID = 'placeholder';
    
    /** @var array */
    private $sessions;

    /** @var int */
    private $duration;

    /** @var DateTime */
    private $time;
    
    /** @var object */
    private $reference = null;
    
    public function __construct($sessions, $time = null)
    {
        if ($sessions) {
            // create sessions loop
            $sessionsCount = count($sessions);
            foreach ($sessions as $index => &$session) {
//                $session->prev = $index > 0 ? $sessions[$index - 1] : $sessions[$sessionsCount - 1];
                $session->next = ($index + 1 < $sessionsCount) ? $sessions[$index + 1] : $sessions[0];
            }
            
            $this->sessions = $sessions;
            
            $this->duration = array_reduce($sessions, function ($carry, $session) {
                return $carry + $session->duration;
            }, 0);

            if ($time !== null) {
                $this->setTime($time);
            }
        }
    }

    /**
     * @param DateTime $time
     * @return object
     */
    private function getMostRecent($time)
    {
        $session = null;
        foreach ($this->sessions as $session) {
            if ($session->date < $time) {
                return $session;
            }
        }
        // $time is before first session
        $placeholder = new \stdClass();
        $placeholder->id = self::PLACEHOLDER_ID;
        $placeholder->date = clone $time;
        $placeholder->duration = $session->date->format('U') - $placeholder->date->format('U');
        $placeholder->offset = 0;
        $placeholder->next = $session;
//        $placeholder->prev = $placeholder;
        $placeholder->color = '#fff';
        return null;
    }
    
    public function setTime($time)
    {
        if (!$time instanceof DateTime) {
            $this->time = DateTime::createFromFormat('U', $time);
        }
        else {
            $this->time = clone $time;
        }
        $this->reference = null;
    }

    /**
     * Interrupting session is a session whose publication
     * occurs during reference session.
     * @return object|null Interrupting session or null if none
     */
    private function getInterruptingSession()
    {
        if ($this->reference !== null) {
            $start = (int)$this->time->format('U');
            $end = $start + $this->reference->duration;
            for ($i = count($this->sessions) - 1; $i >= 0; --$i) {
                $session = $this->sessions[$i];
                $publication = $session->date->format('U');
                if ($publication > $start && $publication < $end) {
                    return $session;
                }
            }
        }
        return null;
    }
    
    public function next()
    {
        if ($this->reference) {
            
            // move time
            $this->time->add(DateInterval::createFromDateString(
                sprintf('%d seconds', $this->reference->duration)
            ));
            
            // move to next published session
            do {
                $this->reference = $this->reference->next;
            } while ($this->reference->date > $this->time);
            
            // look for publications during current session
            $interrupting = $this->getInterruptingSession();
            if ($interrupting) {
                // reference is truncated by publication of a new session
                $this->reference = clone $this->reference;
                $this->reference->duration = $interrupting->date->format('U') - $this->time->format('U');
                $this->reference->next = $interrupting;
            }
        }
        else {
            $this->reference = $this->getMostRecent($this->time);

            // time since reference publication
            $offset = $this->time->format('U') - $this->reference->date->format('U');

            while ($offset >= $this->reference->duration) {
                $offset -= $this->reference->duration;

                // move to next published session
                do {
                    $this->reference = $this->reference->next;
                } while ($this->reference->date > $this->time);
            }

            if ($offset) {
                $this->reference = clone $this->reference;
                $this->reference->offset = $offset;
                $this->reference->duration = $this->reference->duration - $offset;
            }
        }
        
        return $this->reference;
    }
//    
//    /**
//     * @return float Play offset of current session
//     */
//    public function getOffset()
//    {
//        return $this->offset;
//    }

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

//    /**
//     * @param $index
//     * @return object
//     */
//    public function getSession($index)
//    {
//        return $this->sessions[$index % count($this->sessions)];
//    }

    /**
     * @return array the array representation of the object or null
     * @since 5.1.0
     */
    public function toArray()
    {
        if (!$this->reference) {
            $this->next();
        }
        $dateFormat = 'Y-m-d H:i:s';
        $output = get_object_vars($this);
        unset($output['reference']);
        unset($output['sessions']);
        
        foreach ($output as $key => $value) {
            /*if ($key === 'sessions') {
                // to get sessions loop until first one is found again 
                foreach ($output[$key] as $index => $session) {
                    $output[$key][$index] = get_object_vars($session);
                    
                    foreach ($output[$key][$index] as $skey => $svalue) {
                        if ($svalue instanceof DateTime) {
                            $output[$key][$index][$skey] = $svalue->format($dateFormat);
                        }
                    }
                }
            }
            else*/if ($value instanceof DateTime) {
                $output[$key] = $value->format($dateFormat);
            }
        }
        
        $output['offset'] = $this->reference->offset;
        $session = clone $this->reference;
        $output['sessions'] = [];
        do {
            $output['sessions'][] = $this->sessionToArray($session, $dateFormat);
            $session = $session->next;
        } while ($session->id !== $this->reference->id);
        return $output;
    }
    
    private function sessionToArray($session, $dateFormat)
    {
        $output = get_object_vars($session);
        unset($output['next']);
        foreach ($output as $key => $value) {
            if ($value instanceof DateTime) {
                $output[$key] = $value->format($dateFormat);
            }
        }
        return $output;
    }
}