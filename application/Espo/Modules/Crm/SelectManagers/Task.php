<?php

namespace Espo\Modules\Crm\SelectManagers;

class Task extends \Espo\Core\SelectManagers\Base
{
	protected function getBoolFilterWhereActive()
	{
		return array(
			'type' => 'notIn',
			'field' => 'status',
			'value' => array('Completed', 'Canceled'),
		);
	}
	
	protected function getBoolFilterWhereInactive()
	{
		return array(
			'type' => 'in',
			'field' => 'status',
			'value' => array('Completed', 'Canceled'),
		);
	}

}
