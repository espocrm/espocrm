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
    \Espo\Core\Exceptions\Forbidden;

class Admin extends \Espo\Core\Controllers\Base
{
	protected function checkGlobalAccess()
	{
		if (!$this->getUser()->isAdmin()) {
			throw new Forbidden();
		}
	}

	public function actionRebuild($params, $data)
	{
		$result = $this->getContainer()->get('dataManager')->rebuild();

		return $result;
	}

	public function actionClearCache($params, $data)
	{
		$result = $this->getContainer()->get('dataManager')->clearCache();

		return $result;
	}

	public function actionJobs()
	{
		$scheduledJob = $this->getContainer()->get('scheduledJob');

		return $scheduledJob->getAllNamesOnly();
	}

	public function actionUploadUpgradePackage($params, $data)
	{
		$upgradeManager = new \Espo\Core\UpgradeManager($this->getContainer());

		$upgradeId = $upgradeManager->upload($data);
		$manifest = $upgradeManager->getManifest();

		return array(
			'id' => $upgradeId,
			'version' => $manifest['version'],
		);
	}

	public function actionRunUpgrade($params, $data)
	{
		$upgradeManager = new \Espo\Core\UpgradeManager($this->getContainer());

		$upgradeManager->run($data['id']);

		return true;
	}

}

