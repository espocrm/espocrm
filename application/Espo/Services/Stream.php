<?php

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class Stream extends \Espo\Core\Services\Base
{	
	protected $dependencies = array(
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
	
	public function unfollowAllUsersFromEntity(Entity $entity)
	{
		if (empty($entity->id)) {
			return;
		}
		
		$pdo = $this->getEntityManager()->getPDO();
		$sql = "
			DELETE FROM subscription
			WHERE 
				entity_id = " . $pdo->quote($entity->id) . " AND entity_type = " . $pdo->quote($entity->getEntityName()) . "
		";
		$sth = $pdo->prepare($sql)->execute();	
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
			if ($e->get('type') == 'Post') {
				$e->loadLinkMultipleField('attachments');
			}
		}
		
		foreach ($collection as $e) {
			if ($e->get('parentId') && $e->get('parentType')) {
				$entity = $this->getEntityManager()->getEntity($e->get('parentType'), $e->get('parentId'));
				if ($entity) {
					$e->set('parentName', $entity->get('name'));
				}
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
		
		foreach ($collection as $e) {
			if ($e->get('type') == 'Post') {
				$e->loadLinkMultipleField('attachments');
			}
		}
		
		$count = $this->getEntityManager()->getRepository('Note')->count(array(
			'whereClause' => $where,
		));
		
    	return array(
    		'total' => $count,
    		'collection' => $collection,
    	);		
	}
	
	protected function loadAssignedUserName(Entity $entity)
	{
		$user = $this->getEntityManager()->getEntity('User', $entity->get('assignedUserId'));
		if ($user) {
			$entity->set('assignedUserName', $user->get('name'));
		}
	}
	
	public function noteCreate(Entity $entity)
	{
		$note = $this->getEntityManager()->getEntity('Note');
		
		$note->set('type', 'Create');		
		$note->set('parentId', $entity->id);
		$note->set('parentType', $entity->getEntityName());

		if ($entity->get('assignedUserId') != $entity->get('createdById')) {
			if (!$entity->has('assignedUserName')) {
				$this->loadAssignedUserName($entity);
			}
			$note->set('data', json_encode(array(
				'assignedUserId' => $entity->get('assignedUserId'),
				'assignedUserName' => $entity->get('assignedUserName'),
			)));			
		}
		$this->getEntityManager()->saveEntity($note);
	}
}

