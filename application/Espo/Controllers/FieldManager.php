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

use \Espo\Core\Exceptions\Error,
	\Espo\Core\Exceptions\Forbidden,
	\Espo\Core\Exceptions\NotFound;

class FieldManager extends \Espo\Core\Controllers\Base
{
	protected function checkGlobalAccess()
	{
		if (!$this->getUser()->isAdmin()) {
			throw new Forbidden();
		}
	}

	public function actionRead($params, $data)
	{
		$data = $this->getContainer()->get('fieldManager')->read($params['name'], $params['scope']);

		if (!isset($data)) {
			throw new NotFound();
		}

		return $data;
	}

	public function actionCreate($params, $data)
	{
		if (empty($data['name'])) {
			throw new Error("Field 'name' cannnot be empty");
		}

		$name = $data['name'];
		unset($data['name']);

		return $this->getContainer()->get('fieldManager')->create($name, $data, $params['scope']);
	}

	public function actionUpdate($params, $data)
	{
		return $this->getContainer()->get('fieldManager')->update($params['name'], $data, $params['scope']);
	}

	public function actionDelete($params, $data)
	{
		return $this->getContainer()->get('fieldManager')->delete($params['name'], $params['scope']);
	}

}

