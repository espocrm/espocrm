<?php

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class Stream extends \Espo\Core\Services\Base
{
	protected $statusDefs = array(
		'Lead' => array(
			'field' => 'status',
			'style' => array(
				'New' => 'warning',				
				'Assigned' => 'primary',
				'In Process' => 'primary',
				'Converted' => 'success',
				'Recycled' => 'danger',
				'Dead' => 'danger',
			), 
		),
		'Case' => array(
			'field' => 'status',
			'style' => array(
				'New' => 'warning',
				'Assigned' => 'primary',
				'Pending' => 'default',
				'Closed' => 'success',
				'Rejected' => 'danger',
				'Duplicate' => 'danger',
			), 
		),
		'Opportunity' => array(
			'field' => 'stage',
			'style' => array(				
				'Closed Won' => 'success',
				'Closed Lost' => 'danger',
			), 
		),		
	);
	
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
	
	public function checkIsFollowed(Entity $entity, $userId = null)
	{
		if (empty($userId)) {
			$userId = $this->getUser()->id;
		}
	
		$pdo = $this->getEntityManager()->getPDO();
		$sql = "
			SELECT id FROM subscription 
			WHERE 
				entity_id = " . $pdo->quote($entity->id) . " AND entity_type = " . $pdo->quote($entity->getEntityName()) . " AND
				user_id = " . $pdo->quote($userId) . "
		";
		
		$sth = $pdo->prepare($sql);
		$sth->execute();
		if ($sth->fetchAll()) {
			return true;
		}
		return false;
	}
	
	public function followEntity(Entity $entity, $userId)
	{
		if (!$this->getMetadata()->get('scopes.' . $entity->getEntityName() . '.stream')) {
			throw new Error();
		}
		
		$pdo = $this->getEntityManager()->getPDO();
			
		if (!$this->checkIsFollowed($entity, $userId)) {
			$sql = "
				INSERT INTO subscription
				(entity_id, entity_type, user_id)
				VALUES
				(".$pdo->quote($entity->id) . ", " . $pdo->quote($entity->getEntityName()) . ", " . $pdo->quote($userId).")
			";
			$sth = $pdo->prepare($sql)->execute();
		}
		return true;
	}
	
	public function unfollowEntity(Entity $entity, $userId)
	{
		if (!$this->getMetadata()->get('scopes.' . $entity->getEntityName() . '.stream')) {
			throw new Error();
		}		
		
		$pdo = $this->getEntityManager()->getPDO();

		$sql = "
			DELETE FROM subscription
			WHERE 
				entity_id = " . $pdo->quote($entity->id) . " AND entity_type = " . $pdo->quote($entity->getEntityName()) . " AND
				user_id = " . $pdo->quote($userId) . "
		";
		$sth = $pdo->prepare($sql)->execute();
		
		return true;
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
	
	public function noteEmail(Entity $entity, Entity $email)
	{
		$entityName = $entity->getEntityName();
		
		$note = $this->getEntityManager()->getEntity('Note');
		
		$note->set('type', 'Email');
		$note->set('parentId', $entity->id);
		$note->set('parentType', $entityName);
		
		$data = array();
		
		$data['emailId'] = $email->id;
		$data['emailName'] = $email->get('name');	
		
		$note->set('data', json_encode($data));
						
		$this->getEntityManager()->saveEntity($note);
	}
	
	public function noteCreate(Entity $entity)
	{
		$entityName = $entity->getEntityName();
		
		$note = $this->getEntityManager()->getEntity('Note');
		
		$note->set('type', 'Create');
		$note->set('parentId', $entity->id);
		$note->set('parentType', $entityName);

		$data = array();

		if ($entity->get('assignedUserId') != $entity->get('createdById')) {
			if (!$entity->has('assignedUserName')) {
				$this->loadAssignedUserName($entity);
			}			
			$data['assignedUserId'] = $entity->get('assignedUserId');
			$data['assignedUserName'] = $entity->get('assignedUserName');		
		}
		
		if (array_key_exists($entityName, $this->statusDefs)) {
			$field = $this->statusDefs[$entityName]['field'];
			$value = $entity->get($field);
			if (!empty($value)) {				
				$style = 'default';			
				if (!empty($this->statusDefs[$entityName]['style'][$value])) {
					$style = $this->statusDefs[$entityName]['style'][$value];
				}			
				$data['statusValue'] = $value;
				$data['statusField'] = $value;
				$data['statusStyle'] = $style; 
			}
		}
		
		$note->set('data', json_encode($data));
		
		$this->getEntityManager()->saveEntity($note);
	}
	
	public function noteAssign(Entity $entity)
	{
		$note = $this->getEntityManager()->getEntity('Note');
		
		$note->set('type', 'Assign');		
		$note->set('parentId', $entity->id);
		$note->set('parentType', $entity->getEntityName());

		if (!$entity->has('assignedUserName')) {
			$this->loadAssignedUserName($entity);
		}
		$note->set('data', json_encode(array(
			'assignedUserId' => $entity->get('assignedUserId'),
			'assignedUserName' => $entity->get('assignedUserName'),
		)));			

		$this->getEntityManager()->saveEntity($note);
	}
	
	public function noteStatus(Entity $entity, $field)
	{
		$note = $this->getEntityManager()->getEntity('Note');
		
		$note->set('type', 'Status');		
		$note->set('parentId', $entity->id);
		$note->set('parentType', $entity->getEntityName());
		
		$style = 'default';		
		$entityName = $entity->getEntityName();		
		$value = $entity->get($field);
		
		if (!empty($this->statusDefs[$entityName]) && !empty($this->statusDefs[$entityName]['style'][$value])) {
			$style = $this->statusDefs[$entityName]['style'][$value];
		}
		
		$note->set('data', json_encode(array(
			'field' => $field,
			'value' => $value,
			'style' => $style,
		)));
		
		$this->getEntityManager()->saveEntity($note);		
	}
	
	protected function getAuditedFields(Entity $entity)
	{
		$entityName = $entity->getEntityName();	
			
		if (!array_key_exists($entityName, $this->auditedFieldsCache)) {		
			$fields = $this->getMetadata()->get('entityDefs.' . $entityName . '.fields');
			$auditedFields = array();				
			foreach ($fields as $field => $d) {
				if (!empty($d['audited'])) {
					$attributes = array();
					$fieldsDefs = $this->getMetadata()->get('fields.' . $d['type']);
					
					if (empty($fieldsDefs['actualFields'])) {
						$attributes[] = $field;
					} else {
						foreach ($fieldsDefs['actualFields'] as $part) {
							if (!empty($fieldsDefs['naming']) && $fieldsDefs['naming'] == 'prefix') {
								$attributes[] = $part . ucfirst($field);
							} else {
								$attributes[] = $field . ucfirst($part);
							}
						}
					}
				
					$auditedFields[$field] = $attributes;
				}
			}
			$this->auditedFieldsCache[$entityName] = $auditedFields;
		}
				
		return $this->auditedFieldsCache[$entityName];
	}
	
	public function handleAudited($entity)
	{
		$auditedFields = $this->getAuditedFields($entity);
		
		$updatedFields = array();
		$was = array();
		$became = array();
		
		foreach ($auditedFields as $field => $attrs) {
			foreach ($attrs as $attr) {
				$updated = false;
				if ($entity->get($attr) != $entity->getFetched($attr)) {
					$was[$attr] = $entity->getFetched($attr);
					$became[$attr] = $entity->get($attr);
					$updated = true;					
				}
				if ($updated) {
					$updatedFields[] = $field;
				}
			}
		}
				
		if (!empty($updatedFields)) {
			$note = $this->getEntityManager()->getEntity('Note');
		
			$note->set('type', 'Update');		
			$note->set('parentId', $entity->id);
			$note->set('parentType', $entity->getEntityName());

			$note->set('data', json_encode(array(
				'fields' => $updatedFields,
				'attributes' => array(
					'was' => $was,
					'became' => $became,
				)
			)));			

			$this->getEntityManager()->saveEntity($note);
		}
	}
}

