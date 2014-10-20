<?php

namespace Espo\Core\Mail\Storage;

class Imap extends \Zend\Mail\Storage\Imap
{    
    public function getIdsFromUID($uid)
    {
        $uid = intval($uid) + 1;
        return $this->protocol->search(array('UID ' . $uid . ':*'));
    }
    
    public function getIdsFromDate($date)
    {        
        return $this->protocol->search(array('SINCE "' . $date . '"'));
    }
    
}

