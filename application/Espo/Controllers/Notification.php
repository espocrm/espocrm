<?php

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;

class Notification extends \Espo\Core\Controllers\Base
{
	public static $defaultAction = 'list';

	public function actionList($params, $data, $request)
	{
		$scope = $params['scope'];
		$id = $params['id'];
		
		$userId = $this->getUser()->id;
		
		$offset = intval($request->get('offset'));
		$maxSize = intval($request->get('maxSize'));		
		
		$params = array(
			'offset' => $offset,
			'maxSize' => $maxSize,
		);
		
		$result = $this->getService('Notification')->getList($userId, $params);	
		
		return array(
			'total' => $result['total'],
			'list' => $result['collection']->toArray()
		);
	}
	
	public function actionNotReadCount()
	{
		$userId = $this->getUser()->id;
		return $this->getService('Notification')->getNotReadCount($userId);	
	}
}

