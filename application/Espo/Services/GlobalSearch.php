<?php

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
	
	public function find($query, $offset)
	{
		$entityNameList = array(
			'Account',
			'Contact',
			'Lead',
			'Prospect',
			'Opportunity',
		);
		
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

