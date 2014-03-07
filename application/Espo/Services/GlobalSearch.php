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

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class GlobalSearch extends \Espo\Core\Services\Base
{
	
	protected $dependencies = array(
		'entityManager',
		'user',
		'metadata',
		'acl',
		'selectManagerFactory',
		'config',
	);
	
	protected function getSelectManagerFactory()
	{
		return $this->injections['selectManagerFactory'];
	}

	protected function getEntityManager()
	{
		return $this->injections['entityManager'];
	}

	protected function getUser()
	{
		return $this->injections['user'];
	}
	
	protected function getAcl()
	{
		return $this->injections['acl'];
	}
	
	protected function getMetadata()
	{
		return $this->injections['metadata'];
	}
	
	protected function getConfig()
	{
		return $this->injections['config'];
	}
	
	public function find($query, $offset)
	{
		$entityNameList = $this->getConfig()->get('globalSearchEntityList');
		
		$list = array();
		$count = 0;
		$total = 0;
		foreach ($entityNameList as $entityName) {	
		
			if (!$this->getAcl()->check($entityName, 'read')) {
				continue;
			}
			$selectManager = $this->getSelectManagerFactory()->create($entityName);			
			
			$searchParams = array(
				'whereClause' => array(
					'OR' => array(
						'name*' => '%' . $query . '%',
					)
				),
				'offset' => $offset,
				'limit' => 5,
				'orderBy' => 'createdAt',
				'order' => 'DESC',
			);
			$selectParams = array_merge_recursive($searchParams, $selectManager->getAclParams());
			
			$collection = $this->getEntityManager()->getRepository($entityName)->find($selectParams);
			$count += count($collection);
			$total += $this->getEntityManager()->getRepository($entityName)->count($selectParams);
			foreach ($collection as $entity) {
				$data = $entity->toArray();
				$data['_scope'] = $entityName;
				$list[] = $data;
			}
		}
		
		return array(
			'total' => $total,
			'list' => $list,
		);
	}
}

