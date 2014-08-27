<?php

namespace Espo\Core\Mail\Storage;

class Imap extends \Zend\Mail\Storage\Imap
{	
	public function getIdsFromUID($uid)
	{
		$uid = intval($lastUID) + 1;
		return $this->protocol->search(array('UID ' . $uid . ':*'));
	}
}

