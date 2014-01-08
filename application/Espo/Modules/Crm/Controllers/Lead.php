<?php

namespace Espo\Modules\Crm\Controllers;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\BadRequest;

class Lead extends \Espo\Core\Controllers\Record
{
	public function actionConvert($params, $data)
	{		
		if (empty($data['id'])) {
    		throw new BadRequest();
		}
		$entity = $this->getRecordService()->convert($data['id'], $data['records']);
		
		if (!empty($entity)) {
			return $entity->toArray();
		}
		throw new Error();		
	}
}
