<?php

namespace Espo\Modules\Crm\Controllers;

use \Espo\Core\Exceptions\Error,
	\Espo\Core\Exceptions\Forbidden,
	\Espo\Core\Exceptions\BadRequest;

class Activities extends \Espo\Core\Controllers\Base
{
	public static $defaultAction = 'index';
	
	protected $serviceClassName = '\\Espo\\Modules\\Crm\\Services\\Activities';
	
	public function actionListEvents($params, $data, $request)
	{
		$from = $request->get('from');
		$to = $request->get('to');
		
		if (empty($from) || empty($to)) {
			throw new BadRequest();
		}
		
		$service = $this->getService($this->serviceClassName);
		return $service->getEvents($from, $to);
	}

	public function actionList($params, $data, $request)
	{
		$name = $params['name'];
		$entityName = $params['scope'];
		$id = $params['id'];
		
		$offset = intval($request->get('offset'));
		$maxSize = intval($request->get('maxSize'));
		$asc = $request->get('asc') === 'true';
		$sortBy = $request->get('sortBy');
		$where = $request->get('where');
		
		$scope = null;
		if (!empty($where) && !empty($where['scope']) && $where['scope'] !== 'false') {
			$scope = $where['scope'];
		}		
		
		$service = $this->getService($this->serviceClassName);

		$methodName = 'get' . ucfirst($name);
		
		return $service->$methodName($entityName, $id, array(
			'scope' => $scope,
			'offset' => $offset,
			'maxSize' => $maxSize,
			'asc' => $asc,
			'sortBy' => $sortBy,
		));
	}
}

