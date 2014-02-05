<?php

namespace Espo\Modules\Crm\SelectManagers;

class CaseObj extends \Espo\Core\SelectManagers\Base
{

	protected function getBoolFilterWhereOpen()
	{
		return array(
			'type' => 'notIn',
			'field' => 'status',
			'value' => array('Closed', 'Rejected', 'Duplicate'),
		);
	}

}
