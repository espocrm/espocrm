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

use Espo\Core\Exceptions\Error;

class ActionManager
{
	private $managerName;

	private $container;

	private $objects;

	protected $currentAction;

	protected $params;

	public function __construct($managerName, $container, $params)
	{
		$this->managerName = $managerName;
		$this->container = $container;

		$params['name'] = $managerName;
		$this->params = $params;
	}

	protected function getManagerName()
	{
		return $this->managerName;
	}

	protected function getContainer()
	{
		return $this->container;
	}

	public function setAction($action)
	{
		$this->currentAction = $action;
	}

	protected function getAction()
	{
		return $this->currentAction;
	}

	protected function getParams()
	{
		return $this->params;
	}

	public function run($data)
	{
		$object = $this->getObject();

		return $object->run($data);
	}

	public function getManifest()
	{
		return $this->getObject()->getManifest();
	}

	protected function getObject()
	{
		$managerName = $this->getManagerName();
		$actionName = $this->getAction();

		if (!isset($this->objects[$managerName][$actionName])) {
			$class = '\Espo\Core\Upgrades\Actions\\' . ucfirst($managerName) . '\\' . ucfirst($actionName);

			if (!class_exists($class)) {
				throw new Error('Could not find class ['.$class.'].');
			}

			$this->objects[$managerName][$actionName] = new $class($this->container, $this->getParams());
		}

		return $this->objects[$managerName][$actionName];
	}
}