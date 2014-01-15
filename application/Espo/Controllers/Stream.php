<?php

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;

class Stream extends \Espo\Core\Controllers\Base
{
	public static $defaultAction = 'list';

    public function actionList($params, $data, $request)
	{
		$scope = $params['scope'];
		$id = $params['id'];
		
		$offset = intval($request->get('offset'));
		$maxSize = intval($request->get('maxSize'));
		
		$service = $this->getService('\\Espo\\Services\\Stream');		
		
		$result = $service->find($scope, $id, array(
			'offset' => $offset,
			'maxSize' => $maxSize
		));
		
		return array(
			'total' => $result['total'],
			'list' => $result['collection']->toArray()
		);
	}
}

