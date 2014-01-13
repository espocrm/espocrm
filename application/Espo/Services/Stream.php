<?php

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

class Stream extends \Espo\Core\Services\Base
{	
	static public $dependencies = array(
		'entityManager',
		'user',
		'metadata',
		'acl'
	);

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
	
	public function find($scope, $id, $params)
	{
		$entity = $this->getEntityManager()->getEntity($scope, $id);
		
		if (empty($entity)) {
			throw new NotFound();
		}
		
		if (!$this->getAcl($entity, 'read')) {
			throw new Forbidden();
		}
		
		$where = array(
			'parentType' => $scope,
			'parentId' => $id
		);
		
		$collection = $this->getEntityManager()->getRepository('Note')->find(array(
			'whereClause' => $where,
			'offset' => $params['offset'],
			'limit' => $params['maxSize'],
			'orderBy' => 'createdAt',
			'order' => 'DESC'
		));
		
		$count = $this->getEntityManager()->getRepository('Note')->count(array(
			'whereClause' => $where,
		));
		
    	return array(
    		'total' => $count,
    		'collection' => $collection,
    	);		
	}
}

