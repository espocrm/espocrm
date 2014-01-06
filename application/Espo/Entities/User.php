<?php

namespace Espo\Entities;

class User extends \Espo\Core\ORM\Entity
{
	public function isAdmin()
	{
		return $this->get('isAdmin');
	}
}
