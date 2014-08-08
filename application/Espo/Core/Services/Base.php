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

namespace Espo\Core\Services;

use \Espo\Core\Interfaces\Injectable;

abstract class Base implements Injectable
{
	protected $dependencies = array(
		'config',
		'entityManager',
		'user',
	);
	
	protected $injections = array();
	
	public function inject($name, $object)
	{
		$this->injections[$name] = $object;
	}
	
	public function __construct()
	{
		$this->init();
	}
	
	protected function init()
	{	
	}
	
	protected function getInjection($name)
	{
		return $this->injections[$name];
	}
	
	public function getDependencyList()
	{
		return $this->dependencies;
	}
	
	protected function getEntityManager()
	{
		return $this->getInjection('entityManager');
	}
	
	protected function getConfig()
	{
		return $this->getInjection('config');
	}

	protected function getUser()
	{
		return $this->getInjection('user');
	}
}

