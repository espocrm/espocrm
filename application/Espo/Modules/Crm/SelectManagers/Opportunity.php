<?php

namespace Espo\Modules\Crm\SelectManagers;

class Opportunity extends \Espo\Core\SelectManager
{

	protected function getBoolFilterWhereOpen()
	{
		return array(
			'type' => 'notIn',
			'field' => 'stage',
			'value' => array('Closed Won', 'Closed Lost'),
		);
	}

}
