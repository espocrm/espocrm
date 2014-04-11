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

namespace Espo\Core\Upgrades;

use Espo\Core\Utils\Util,
	Espo\Core\Exceptions\Error;

abstract class Base
{
	private $container;

	private $zipUtil;

	private $fileManager;

	private $config;

	protected $upgradeId = null;

	protected $mainFileName = 'main.php';

	protected $data;

	protected $packagePath = null;

	protected $packagePostfix = 'z';

	protected $scriptNames = array(
		'before' => 'Before',
		'after' => 'After',
	);

	protected $paths = array(
		'files' => 'Files',
		'scripts' => 'Scripts',
	);


	public function __construct($container)
	{
		$this->container = $container;

		$this->zipUtil = new \Espo\Core\Utils\File\ZipArchive($container->get('fileManager'));
	}

	public function __destruct()
	{
		$this->upgradeId = null;
		$this->data = null;
	}

	protected function getContainer()
	{
		return $this->container;
	}

	protected function getZipUtil()
	{
		return $this->zipUtil;
	}

	protected function getFileManager()
	{
		if (!isset($this->fileManager)) {
			$this->fileManager = $this->getContainer()->get('fileManager');
		}
		return $this->fileManager;
	}

	protected function getConfig()
	{
		if (!isset($this->config)) {
			$this->config = $this->getContainer()->get('config');
		}
		return $this->config;
	}


	/**
	 * Upload an upgrade package
	 *
	 * @param  [type] $contents
	 * @return string  ID of upgrade process
	 */
	public function upload($data)
	{
		$upgradeId = $this->createUpgradeId();

		$upgradePath = $this->getUpgradePath();
		$upgradePackagePath = $this->getUpgradePath(true);

		if (!empty($data)) {
			list($prefix, $contents) = explode(',', $data);
			$contents = base64_decode($contents);
		}

		$res = $this->getFileManager()->putContents($upgradePackagePath, $contents);
		if ($res === false) {
			throw new Error('Could not upload the package.');
		}

		$res = $this->getZipUtil()->unzip($upgradePackagePath, $upgradePath);
		if ($res === false) {
			throw new Error('Unnable to unzip the file - '.$upgradePath.'.');
		}

		if (!$this->isAcceptable()) {
			throw new Error("Your EspoCRM version doesn't match for this upgrade package.");
		}

		return $upgradeId;
	}

	/**
	 * Main upgrade process
	 *
	 * @param  string $upgradeId Upgrade ID, gotten in upload stage
	 * @return bool
	 */
	public function run($upgradeId)
	{
		$this->setUpgradeId($upgradeId);

		/* run before install script */
		$this->runScript('before');

		/* remove files defined in a mainFile */
		if (!$this->deleteFiles()) {
			throw new Error('Permission denied to delete files.');
		}

		/* copy files from directory "Files" to EspoCRM files */
		if (!$this->copyFiles()) {
			throw new Error('Cannot copy files.');
		}

		$this->getContainer()->get('dataManager')->rebuild();

		/* run before install script */
		$this->runScript('after');

		/* delete unziped files */
		$this->deletePackageFiles();
	}


	protected function createUpgradeId()
	{
		if (isset($this->upgradeId)) {
			throw new Error('Another upgrade process is currently running.');
		}

		$this->upgradeId = uniqid('upg');

		return $this->upgradeId;
	}

	protected function getUpgradeId()
	{
		if (!isset($this->upgradeId)) {
			throw new Error("Upgrade ID was not specified.");
		}

		return $this->upgradeId;
	}

	protected function setUpgradeId($upgradeId)
	{
		$this->upgradeId = $upgradeId;
	}

	/**
	 * Check if version of upgrade is acceptable to current version of EspoCRM
	 *
	 * @param  string  $version
	 * @return boolean
	 */
	protected function isAcceptable()
	{
		$version = $this->getMainFile()['acceptableVersions'];

		$currentVersion = $this->getConfig()->get('version');

		if (is_string($version)) {
			$version = (array) $version;
		}

		foreach ($version as $strVersion) {

			$strVersion = trim($strVersion);

			if ($strVersion == $currentVersion) {
				return true;
			}

			$strVersion = str_replace('\\', '', $strVersion);
			$strVersion = preg_quote($strVersion);
			$strVersion = str_replace('\\*', '+', $strVersion);

			if (preg_match('/^'.$strVersion.'/', $currentVersion)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Run scripts by type
	 * @param  string $type Ex. "before", "after"
	 * @return void
	 */
	protected function runScript($type)
	{
		$upgradePath = $this->getUpgradePath();

		$scriptName = $this->scriptNames[$type];
		if (!isset($scriptName)) {
			return;
		}

		$beforeInstallScript = Util::concatPath( array($upgradePath, $this->paths['scripts'], $scriptName) );

		if (file_exists($beforeInstallScript)) {
			require_once($beforeInstallScript);
			$script = new $scriptName();
			$script->run($this->getContainer());
		}
	}

	/**
	 * Get upgrade path
	 *
	 * @param  string $upgradeId
	 * @return string
	 */
	protected function getUpgradePath($isPackage = false)
	{
		$postfix = $isPackage ? $this->packagePostfix : '';

		if (!isset($this->data['upgradePath'])) {
			$upgradeId = $this->getUpgradeId();
			$this->data['upgradePath'] = Util::concatPath($this->packagePath, $upgradeId);
		}

		return $this->data['upgradePath'] . $postfix;
	}

	/**
	 * Delete files defined in a main file
	 *
	 * @return boolen
	 */
	protected function deleteFiles()
	{
		$mainFile = $this->getMainFile();

		if (!empty($mainFile['delete'])) {
			return $this->getFileManager()->remove($mainFile['delete']);
		}

		return true;
	}

	/**
	 * Copy files from upgrade package
	 *
	 * @param  string $upgradeId
	 * @return boolean
	 */
	protected function copyFiles()
	{
		$upgradePath = $this->getUpgradePath();
		$filesPath = Util::concatPath($upgradePath, $this->paths['files']);

		return $this->getFileManager()->copy($filesPath, '', true);
	}

	public function getMainFile()
	{
		if (!isset($this->data['mainFile'])) {
			$upgradePath = $this->getUpgradePath();
			$this->data['mainFile'] = $this->getFileManager()->getContents(array($upgradePath, $this->mainFileName));

			if (!$this->checkMainFile($this->data['mainFile'])) {
				throw new Error('Unsupported package');
			}
		}

		return $this->data['mainFile'];
	}

	/**
	 * Check if the main file is correct
	 *
	 * @param  array  $mainFile
	 * @return boolean
	 */
	protected function checkMainFile(array $mainFile)
	{
		$requiredFields = array(
			'name',
			'version',
			'acceptableVersions',
		);

		foreach ($requiredFields as $fieldName) {
			if (empty($mainFile[$fieldName])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Delete temporary package files
	 *
	 * @return boolean
	 */
	protected function deletePackageFiles()
	{
		$upgradePath = $this->getUpgradePath();
		$upgradePackagePath = $this->getUpgradePath(true);

		$res = $this->getFileManager()->removeInDir($upgradePath, true);
		$res &= $this->getFileManager()->removeFile($upgradePackagePath);

		return $res;
	}


}