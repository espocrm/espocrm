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

namespace Espo\Core\Controllers;

use \Espo\Core\Container;
use \Espo\Core\ServiceFactory;
use \Espo\Core\Utils\Util;

abstract class Base
{
	protected $name;
	
	private $container;
	
	public static $defaultAction = 'index';

	public function __construct(Container $container)
	{
		$this->container = $container;
		
		if (empty($this->name)) {
			$name = get_class($this);
			if (preg_match('@\\\\([\w]+)$@', $name, $matches)) {
		    	$name = $matches[1];
			}
			$this->name = $name;
    	}
    	
    	$this->checkGlobalAccess();
	}
	
	protected function checkGlobalAccess()
	{
		return;
	}
	
	protected function getContainer()
	{
		return $this->container;
	}
	
	protected function getUser()
	{
		return $this->container->get('user');
	}
	
	protected function getAcl()
	{
		return $this->container->get('acl');
	}
	
	protected function getConfig()
	{
		return $this->container->get('config');
	}
	
	protected function getPreferences()
	{
		return $this->container->get('preferences');
	}
	
	protected function getMetadata()
	{
		return $this->container->get('metadata');
	}

	protected function getServiceFactory()
	{
		return $this->container->get('serviceFactory');
	}
	
	protected function getService($name)
	{
		return $this->getServiceFactory()->create($name);
	}
}

