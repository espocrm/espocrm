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

namespace Espo\Core\Upgrades\Actions\Base;

use Espo\Core\Exceptions\Error;

class Install extends \Espo\Core\Upgrades\Actions\Base
{
	/**
	 * Main installation process
	 *
	 * @param  string $processId Upgrade/Extension ID, gotten in upload stage
	 * @return bool
	 */
	public function run($processId)
	{
		$GLOBALS['log']->debug('Installation process ['.$processId.']: start run.');

		if (empty($processId)) {
			throw new Error('Installation package ID was not specified.');
		}

		$this->setProcessId($processId);

		/** check if an archive is unzipped, if no then unzip */
		$packagePath = $this->getPath('packagePath');
		if (!file_exists($packagePath)) {
			$this->unzipArchive();
			$this->isAcceptable();
		}

		$this->beforeRunAction();

		/* run before install script */
		$this->runScript('before');

		/* remove files defined in a manifest */
		if (!$this->deleteFiles()) {
			throw new Error('Permission denied to delete files.');
		}

		/* copy files from directory "Files" to EspoCRM files */
		if (!$this->copyFiles()) {
			throw new Error('Cannot copy files.');
		}

		if (!$this->systemRebuild()) {
			throw new Error('Error occurred while EspoCRM rebuild.');
		}

		/* run before install script */
		$this->runScript('after');

		$this->afterRunAction();

		/* delete unziped files */
		$this->deletePackageFiles();

		$GLOBALS['log']->debug('Installation process ['.$processId.']: end run.');
	}
}
