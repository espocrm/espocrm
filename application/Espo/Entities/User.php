<?php

namespace Espo\Entities;

class User extends \Espo\Core\ORM\Entity
{
	public static $person = true;
	
	public function isAdmin()
	{
		return $this->get('isAdmin');
	}
}
