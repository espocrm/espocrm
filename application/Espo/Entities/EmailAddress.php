<?php

namespace Espo\Entities;

class EmailAddress extends \Espo\Core\ORM\Entity
{

	protected function setName($value)
	{
		$this->valuesContainer['name'] = $value;
		$this->set('lower', strtolower($value));		
	} 

}
