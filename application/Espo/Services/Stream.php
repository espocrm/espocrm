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
	
	public function findUserStream($params = array())
	{
		$selectParams = array(
			'offset' => $params['offset'],
			'limit' => $params['maxSize'],
			'orderBy' => 'createdAt',
			'order' => 'DESC',
			'customJoin' => "
				JOIN subscription ON 
					note.parent_type = subscription.entity_type AND 
					note.parent_id = subscription.entity_id AND
					subscription.user_id = '" . $this->getUser()->id . "'
			"
		);
	
		$collection = $this->getEntityManager()->getRepository('Note')->find($selectParams);
		
		foreach ($collection as $e) {
			if ($e->get('type') == 'Post' && $e->get('parentId') && $e->get('parentType')) {
				$entity = $this->getEntityManager()->getEntity($e->get('parentType'), $e->get('parentId'));
				$e->set('parentName', $entity->get('name'));
			}
		}
				
		$count = $this->getEntityManager()->getRepository('Note')->count($selectParams);
    	
    	return array(
    		'total' => $count,
    		'collection' => $collection,
    	);	
	}
	
	public function find($scope, $id, $params = array())
	{
		if ($scope == 'User') {
			return $this->findUserStream($params);
		}
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

