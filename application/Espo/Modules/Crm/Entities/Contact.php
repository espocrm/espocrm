<?php

namespace Espo\Modules\Crm\Entities;

class Contact extends \Espo\ORM\Entity
{

	protected function getName()
	{
		return $this->get('firstName') . ' ' . $this->get('lastName');
	}
}
