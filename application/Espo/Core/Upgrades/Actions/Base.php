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

namespace Espo\Core\Upgrades\Actions;

use Espo\Core\Utils\Util,
	Espo\Core\Utils\Json,
	Espo\Core\Exceptions\Error;

abstract class Base
{
	private $container;

	private $zipUtil;

	private $fileManager;

	private $config;

	private $entityManager;

	protected $data;

	private $params = null;

	protected $processId = null;

	protected $manifestName = 'manifest.json';

	protected $packagePostfix = 'z';

	/**
	 * Directory name of files in a package
	 */
	const FILES = 'files';

	/**
	 * Directory name of scripts in a package
	 */
	const SCRIPTS = 'scripts';

	/**
	 * Statuses of Extension Entity
	 */
	const DISABLED = 'Disabled';

	/**
	 * Statuses of Extension Entity
	 */
	const ENABLED = 'Enabled';

	/**
	 * Package types
	 */
	protected $packageTypes = array(
		'upgrade' => 'upgrade',
		'extension' => 'extension',
	);

	/**
	 * Default package type
	 */
	protected $defaultPackageType = 'extension';


	public function __construct($container, $params)
	{
		$this->container = $container;
		$this->params = $params;

		$this->zipUtil = new \Espo\Core\Utils\File\ZipArchive($container->get('fileManager'));
	}

	public function __destruct()
	{
		$this->processId = null;
		$this->data = null;
	}

	protected function getContainer()
	{
		return $this->container;
	}

	protected function getParams($name = null)
	{
		if (isset($this->params[$name])) {
			return $this->params[$name];
		}

		return $this->params;
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

	protected function getEntityManager()
	{
		if (!isset($this->entityManager)) {
			$this->entityManager = $this->getContainer()->get('entityManager');
		}
		return $this->entityManager;
	}

	protected function throwErrorWithDetelePackage($errorMessage = '')
	{
		$this->deletePackageFiles();
		$this->deletePackageArchive();
		throw new Error($errorMessage);
	}

	abstract public function run($data);

	protected function createProcessId()
	{
		if (isset($this->processId)) {
			throw new Error('Another installation process is currently running.');
		}

		$this->processId = uniqid();

		return $this->processId;
	}

	protected function getProcessId()
	{
		if (!isset($this->processId)) {
			throw new Error('Installation ID was not specified.');
		}

		return $this->processId;
	}

	protected function setProcessId($processId)
	{
		$this->processId = $processId;
	}

	/**
	 * Check if version of upgrade/extension is acceptable to current version of EspoCRM
	 *
	 * @param  string  $version
	 * @return boolean
	 */
	protected function isAcceptable()
	{
		$res = $this->checkPackageType();
		$res &= $this->checkVersions();

		return $res;
	}

	protected function checkVersions()
	{
		$manifest = $this->getManifest();

		/** check acceptable versions */
		$version = $manifest['acceptableVersions'];
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

		$this->throwErrorWithDetelePackage('Your EspoCRM version doesn\'t match for this installation package.');
	}

	protected function checkPackageType()
	{
		$manifest = $this->getManifest();

		/** check package type */
		$type = strtolower( $this->getParams('name') );
		$manifestType = isset($manifest['type']) ? strtolower($manifest['type']) : $this->defaultPackageType;

		if (!in_array($manifestType, $this->packageTypes)) {
			$this->throwErrorWithDetelePackage('Unknown package type.');
		}

		if ($type != $manifestType) {
			$this->throwErrorWithDetelePackage('Wrong package type. You cannot install '.$manifestType.' package via '.ucfirst($type).' Manager.');
		}

		return true;
	}

	/**
	 * Run scripts by type
	 * @param  string $type Ex. "before", "after"
	 * @return void
	 */
	protected function runScript($type)
	{
		$packagePath = $this->getPath('packagePath');
		$scriptNames = $this->getParams('scriptNames');

		$scriptName = $scriptNames[$type];
		if (!isset($scriptName)) {
			return;
		}

		$beforeInstallScript = Util::concatPath( array($packagePath, self::SCRIPTS, $scriptName) ) . '.php';

		if (file_exists($beforeInstallScript)) {
			require_once($beforeInstallScript);
			$script = new $scriptName();
			$script->run($this->getContainer());
		}
	}

	/**
	 * Get package path
	 *
	 * @param  string $processId
	 * @return string
	 */
	protected function getPath($name = 'packagePath', $isPackage = false)
	{
		$postfix = $isPackage ? $this->packagePostfix : '';

		$processId = $this->getProcessId();
		$path = Util::concatPath($this->getParams($name), $processId);

		return $path . $postfix;
	}

	/**
	 * Get a list of files defined in manifest.json
	 *
	 * @return [type] [description]
	 */
	protected function getDeleteFileList()
	{
		$manifest = $this->getManifest();

		if (!empty($manifest['delete'])) {
			return $manifest['delete'];
		}

		return array();
	}

	/**
	 * Delete files defined in a manifest
	 *
	 * @return boolen
	 */
	protected function deleteFiles()
	{
		$deleteFileList = $this->getDeleteFileList();

		if (!empty($deleteFileList)) {
			return $this->getFileManager()->remove($deleteFileList);
		}

		return true;
	}

	protected function getCopyFileList()
	{
		if (!isset($this->data['fileList'])) {
			$packagePath = $this->getPath('packagePath');
			$filesPath = Util::concatPath($packagePath, self::FILES);

			$this->data['fileList'] = $this->getFileManager()->getFileList($filesPath, true, '', 'all', true);
		}

		return $this->data['fileList'];
	}

	/**
	 * Copy files from upgrade/extension package
	 *
	 * @param  string $processId
	 * @return boolean
	 */
	protected function copyFiles()
	{
		$packagePath = $this->getPath('packagePath');
		$filesPath = Util::concatPath($packagePath, self::FILES);

		return $this->getFileManager()->copy($filesPath, '', true);
	}

	public function getManifest()
	{
		if (!isset($this->data['manifest'])) {
			$packagePath = $this->getPath('packagePath');

			$manifestPath = Util::concatPath($packagePath, $this->manifestName);
			if (!file_exists($manifestPath)) {
				throw new Error('It\'s not an Installation package.');
			}

			$manifestJson = $this->getFileManager()->getContents($manifestPath);
			$this->data['manifest'] = Json::decode($manifestJson, true);

			if (!$this->data['manifest']) {
				throw new Error('Syntax error in manifest.json.');
			}

			if (!$this->checkManifest($this->data['manifest'])) {
				throw new Error('Unsupported package.');
			}
		}

		return $this->data['manifest'];
	}

	/**
	 * Check if the manifest is correct
	 *
	 * @param  array  $manifest
	 * @return boolean
	 */
	protected function checkManifest(array $manifest)
	{
		$requiredFields = array(
			'name',
			'version',
			'acceptableVersions',
		);

		foreach ($requiredFields as $fieldName) {
			if (empty($manifest[$fieldName])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Unzip a package archieve
	 *
	 * @return void
	 */
	protected function unzipArchive()
	{
		$packagePath = $this->getPath('packagePath');
		$packageArchivePath = $this->getPath('packagePath', true);

		$res = $this->getZipUtil()->unzip($packageArchivePath, $packagePath);
		if ($res === false) {
			throw new Error('Unnable to unzip the file - '.$packagePath.'.');
		}
	}

	/**
	 * Delete temporary package files
	 *
	 * @return boolean
	 */
	protected function deletePackageFiles()
	{
		$packagePath = $this->getPath('packagePath');
		$res = $this->getFileManager()->removeInDir($packagePath, true);

		return $res;
	}

	/**
	 * Delete temporary package archive
	 *
	 * @return boolean
	 */
	protected function deletePackageArchive()
	{
		$packageArchive = $this->getPath('packagePath', true);
		$res = $this->getFileManager()->removeFile($packageArchive);

		return $res;
	}

	protected function systemRebuild()
	{
		return $this->getContainer()->get('dataManager')->rebuild();
	}

	protected function beforeRunAction()
	{

	}

	protected function afterRunAction()
	{

	}


}
