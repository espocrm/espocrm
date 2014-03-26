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

namespace Espo\Core\Cron;

use Espo\Core\Exceptions\NotFound,
	Espo\Core\Utils\Util;

class ScheduledJob
{
	private $container;

	protected $data = null;

	protected $cacheFile = 'data/cache/application/jobs.php';

	protected $allowedMethod = 'run';

	/**
     * @var array - path to cron job files
     */
	private $paths = array(
		'corePath' => 'application/Espo/Jobs',
    	'modulePath' => 'application/Espo/Modules/{*}/Jobs',
    	'customPath' => 'custom/Espo/Custom/Jobs',
	);


	public function __construct(\Espo\Core\Container $container)
	{
		$this->container = $container;
	}

	protected function getContainer()
	{
		return $this->container;
	}

	protected function getEntityManager()
	{
		return $this->container->get('entityManager');
	}

	public function run(array $job)
	{
		$jobName = $job['method'];

		$className = $this->getClassName($jobName);
		if ($className === false) {
			throw new NotFound();
		}

		$jobClass = new $className($this->container);
		$method = $this->allowedMethod;

		$jobClass->$method();
	}

	/**
	 * Get list of all jobs
	 *
	 * @return array
	 */
	public function getAll()
	{
		if (!isset($this->data)) {
			$this->init();
		}

		return $this->data;
	}

	/**
	 * Get class name of a job by name
	 *
	 * @param  string $name
	 * @return string
	 */
	public function get($name)
	{
		return $this->getClassName($name);
	}

	/**
	 * Get list of all job names
	 *
	 * @return array
	 */
	public function getAllNamesOnly()
	{
		$data = $this->getAll();

		$namesOnly = array_keys($data);

		return $namesOnly;
	}

	/**
	 * Get class name of a job
	 *
	 * @param  string $name
	 * @return string
	 */
	protected function getClassName($name)
	{
		$name = Util::normilizeClassName($name);

		$data = $this->getAll();

		$name = ucfirst($name);
		if (isset($data[$name])) {
			return $data[$name];
		}

        return false;
	}

	/**
	 * Load scheduler classes. It loads from ...Jobs, ex. \Espo\Jobs
	 * @return null
	 */
	protected function init()
	{
		$classParser = $this->getContainer()->get('classParser');
		$classParser->setAllowedMethods( array($this->allowedMethod) );
		$this->data = $classParser->getData($this->paths, $this->cacheFile);
	}

}