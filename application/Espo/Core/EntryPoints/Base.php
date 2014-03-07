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

namespace Espo\Core\EntryPoints;

use \Espo\Core\Container;

use \Espo\Core\Exceptions\Forbidden;

abstract class Base
{
	private $container;
	
	public static $authRequired = true;
	
	protected function getContainer()
	{
		return $this->container;
	}
	
	protected function getUser()
	{
		return $this->getContainer()->get('user');
	}
	
	protected function getAcl()
	{
		return $this->getContainer()->get('acl');
	}
	
	protected function getEntityManager()
	{
		return $this->getContainer()->get('entityManager');
	}
	
	protected function getServiceFactory()
	{
		return $this->getContainer()->get('serviceFactory');
	}	
	
	protected function getConfig()
	{
		return $this->getContainer()->get('config');
	}
	
	protected function getMetadata()
	{
		return $this->getContainer()->get('metadata');
	}	
	
	public function __construct(Container $container)
	{
		$this->container = $container;
	}
	
	abstract public function run();	

}

