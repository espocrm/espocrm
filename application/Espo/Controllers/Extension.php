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
use \Espo\Core\Exceptions\Forbidden;

class Extension extends \Espo\Core\Controllers\Record
{
	protected function checkControllerAccess()
	{
		if (!$this->getUser()->isAdmin()) {
			throw new Forbidden();
		}
	}

	public function actionUpload($params, $data, $request)
	{
		if (!$request->isPost()) {
			throw new Forbidden();
		}

		$manager = new \Espo\Core\ExtensionManager($this->getContainer());

		$id = $manager->upload($data);
		$manifest = $manager->getManifest();

		return array(
			'id' => $id,
			'version' => $manifest['version'],
			'name' => $manifest['name'],
			'description' => $manifest['description'],
		);
	}

	public function actionInstall($params, $data, $request)
	{
		if (!$request->isPost()) {
			throw new Forbidden();
		}

		$manager = new \Espo\Core\ExtensionManager($this->getContainer());

		$manager->install($data['id']);

		return true;
	}

	public function actionUninstall($params, $data, $request)
	{
		if (!$request->isPost()) {
			throw new Forbidden();
		}

		$manager = new \Espo\Core\ExtensionManager($this->getContainer());

		$manager->uninstall($data['id']);

		return true;
	}

	public function actionCreate()
	{
		throw new Forbidden();
	}

	public function actionUpdate()
	{
		throw new Forbidden();
	}

	public function actionPatch()
	{
		throw new Forbidden();
	}

	public function actionListLinked()
	{
		throw new Forbidden();
	}

	public function actionDelete($params, $data, $request)
	{
		$manager = new \Espo\Core\ExtensionManager($this->getContainer());

		$manager->delete($params['id']);

		return true;
	}

	public function actionMassUpdate()
	{
		throw new Forbidden();
	}

	public function actionMassDelete()
	{
		throw new Forbidden();
	}

	public function actionCreateLink()
	{
		throw new Forbidden();
	}

	public function actionRemoveLink()
	{
		throw new Forbidden();
	}
}

