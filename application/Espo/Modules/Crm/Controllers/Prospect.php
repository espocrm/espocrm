<?php

namespace Espo\Modules\Crm\Controllers;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\BadRequest;
	
class Prospect extends \Espo\Core\Controllers\Record
{
	
	public function actionConvert($params, $data)
	{	
		
		if (empty($data['id'])) {
    		throw new BadRequest();
		}
		$entity = $this->getRecordService()->convert($data['id']);
		
		if (!empty($entity)) {
			return $entity->toArray();
		}
		throw new Error();		
	}

}
