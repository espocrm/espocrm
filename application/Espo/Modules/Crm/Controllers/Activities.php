<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/ 

namespace Espo\Modules\Crm\Controllers;

use \Espo\Core\Exceptions\Error,
	\Espo\Core\Exceptions\Forbidden,
	\Espo\Core\Exceptions\BadRequest;

class Activities extends \Espo\Core\Controllers\Base
{
	public static $defaultAction = 'index';	
	
	public function actionListCalendarEvents($params, $data, $request)
	{
		$from = $request->get('from');
		$to = $request->get('to');
		
		if (empty($from) || empty($to)) {
			throw new BadRequest();
		}
		
		
		$service = $this->getService('Activities');
		return $service->getEvents($this->getUser()->id, $from, $to);
	}

	public function actionList($params, $data, $request)
	{
		$name = $params['name'];
		
		if (!in_array($name, array('activities', 'history'))) {
			throw new BadRequest();
		}
		
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
		
		$service = $this->getService('Activities');

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

