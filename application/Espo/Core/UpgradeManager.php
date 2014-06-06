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

namespace Espo\Core;

use Espo\Core\Exceptions\Error;

class UpgradeManager extends Upgrades\Base
{
	protected $packagePath = 'data/upload/upgrades';

	protected $scriptNames = array(
		'before' => 'BeforeUpgrade',
		'after' => 'AfterUpgrade',
	);

	/**
	 * Main upgrade process
	 *
	 * @param  string $upgradeId Upgrade ID, gotten in upload stage
	 * @return bool
	 */
	public function run($upgradeId)
	{
		/** set writable permission for espo */
		$permissionRes = $this->getFileManager()->getPermissionUtils()->setMapPermission(array('0664', '0775'));
		if (!$permissionRes) {
			throw new Error( 'Permission denied for the following items: /'. implode(', /', $this->getFileManager()->getPermissionUtils()->getLastError()) );
		}

		parent::run($upgradeId);

		/** return the previous permission */
		$permissionRes = $this->getFileManager()->getPermissionUtils()->setMapPermission();
		if (!$permissionRes) {
			throw new Error( 'Permission denied for the following items: '. implode(', ', $this->getFileManager()->getPermissionUtils()->getLastError()) );
		}
	}



}