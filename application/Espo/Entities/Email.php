<?php

namespace Espo\Entities;

class Email extends \Espo\Core\ORM\Entity
{

	protected function getSubject()
	{
		return $this->get('name');
	}
	
	protected function setSubject($value)
	{
		return $this->set('name', $value);
	}
}

