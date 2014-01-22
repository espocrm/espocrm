<?php

namespace Espo\Entities;

use Espo\Core\Exceptions\Error;

class EmailAddress extends \Espo\Core\ORM\Entity
{

	protected function setName($value)
	{
		if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
			throw new Error("Not valid email address '{$value}'");
		}
		$this->valuesContainer['name'] = $value;
		$this->set('lower', strtolower($value));		
	} 

}
