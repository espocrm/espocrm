<?php

namespace Espo\Entities;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="users")
 */
class User extends \Espo\ORM\Entity
{
	public function isAdmin()
	{
		return $this->get('isAdmin');
	}	
}
