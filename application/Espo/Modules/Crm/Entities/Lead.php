<?php

namespace Espo\Modules\Crm\Entities;

class Lead extends \Espo\Core\ORM\Entity
{
	protected function getName()
	{
		return $this->get('firstName') . ' ' . $this->get('lastName');
	}
}
