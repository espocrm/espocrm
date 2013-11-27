<?php

namespace Espo\Core;

class Acl
{
	private $data = array();

	public function __construct(\Espo\Entities\User $user)
	{	
		$this->user = $user;
		
	}

	public function check($subject, $action)
	{
		return true;	
	}
}
