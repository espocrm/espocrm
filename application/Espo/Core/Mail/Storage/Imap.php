<?php

namespace Espo\Core\Mail\Storage;

use Zend\Mail\Storage\Imap as ZendImap;

class Imap extends
    ZendImap
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

