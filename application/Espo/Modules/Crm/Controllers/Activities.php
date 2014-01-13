<?php

namespace Espo\Modules\Crm\Controllers;

use \Espo\Core\Exceptions\Error,
	\Espo\Core\Exceptions\Forbidden;

class Activities extends \Espo\Core\Controllers\Base
{
	public static $defaultAction = 'index';
	
	protected $serviceClassName = '\\Espo\\Modules\\Crm\\Services\\Activities';

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

