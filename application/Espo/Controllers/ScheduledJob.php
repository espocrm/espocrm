<?php

namespace Espo\Controllers;

class ScheduledJob extends \Espo\Core\Controllers\Record
{
	protected function checkGlobalAccess()
	{
		if (!$this->getUser()->isAdmin()) {
        	throw new Forbidden();
		}
	}
}

