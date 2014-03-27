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

class DataManager
{
	private $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	protected function getContainer()
	{
		return $this->container;
	}

	/**
	 * Rebuild the system with metadata, database and cache clearing
	 *
	 * @return bool
	 */
	public function rebuild()
	{
		$result = $this->clearCache();

		$result &= $this->rebuildMetadata();

		$result &= $this->rebuildDatabase();

		return $result;
	}

	/**
	 * Clear a cache
	 *
	 * @return bool
	 */
	public function clearCache()
	{
		$cacheDir = $this->getContainer()->get('config')->get('cachePath');

		$result = $this->getContainer()->get('fileManager')->removeInDir($cacheDir);

		if ($result === false) {
			throw new Error("Error while clearing cache");
		}

		return $result;
	}

	/**
	 * Rebuild database
	 *
	 * @return bool
	 */
	public function rebuildDatabase()
	{
		try {
			$result = $this->getContainer()->get('schema')->rebuild();
		} catch (\Exception $e) {
			$result = false;
			$GLOBALS['log']->error('Fault to rebuild database schema'.'. Details: '.$e->getMessage());
		}

		if ($result === false) {
			throw new Error("Error while rebuilding database");
		}

		return $result;
	}

	/**
	 * Rebuild metadata
	 *
	 * @return bool
	 */
	public function rebuildMetadata()
	{
		$metadata = $this->getContainer()->get('metadata');

		$metadata->init(true);

		$ormMeta = $metadata->getOrmMetadata(true);

		return empty($ormMeta) ? false : true;
	}


}