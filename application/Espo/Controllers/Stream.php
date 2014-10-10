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

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;

class Stream extends \Espo\Core\Controllers\Base
{
	const MAX_SIZE_LIMIT = 400;
	
	public static $defaultAction = 'list';

    public function actionList($params, $data, $request)
	{
		$scope = $params['scope'];
		$id = isset($params['id']) ? $params['id'] : null;
		
		$offset = intval($request->get('offset'));
		$maxSize = intval($request->get('maxSize'));
		$after = $request->get('after');
		
		$service = $this->getService('Stream');
		
		if (empty($maxSize)) {
			$maxSize = self::MAX_SIZE_LIMIT;
		}
		if (!empty($maxSize) && $maxSize > self::MAX_SIZE_LIMIT) {
			throw new Forbidden();
		}		
		
		$result = $service->find($scope, $id, array(
			'offset' => $offset,
			'maxSize' => $maxSize,
			'after' => $after,
		));
		
		return array(
			'total' => $result['total'],
			'list' => $result['collection']->toArray()
		);
	}
}

