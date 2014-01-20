<?php

namespace Espo\Modules\Crm\Entities;

class InboundEmail extends \Espo\Core\ORM\Entity
{
	protected function checkGlobalAccess()
	{
		if (!$this->getUser()->isAdmin()) {
        	throw new Forbidden();
		}
	}
}
