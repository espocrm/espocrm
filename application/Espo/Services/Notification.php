<?php

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class Notification extends \Espo\Core\Services\Base
{	
	protected $dependencies = array(
		'entityManager',
		'user',
		'metadata',
	);

	protected function getEntityManager()
	{
		return $this->injections['entityManager'];
	}

	protected function getUser()
	{
		return $this->injections['user'];
	}	
	
	protected function getMetadata()
	{
		return $this->injections['metadata'];
	}
	
	public function notifyAboutNote($userId, $noteId)
	{
		$notification = $this->getEntityManager()->getEntity('Notification');		
		$notification->set(array(
			'type' => 'Note',
			'data' => json_encode(array('noteId' => $noteId)),
			'userId' => $userId
		));
		$this->getEntityManager()->saveEntity($notification);		
	}
	
	public function getNotReadCount($userId)
	{
		$searchParams = array();
		$searchParams['whereClause'] = array(
			'userId' => $userId
		);		
		return $this->getEntityManager()->getRepository('Notification')->where(array(
			'userId' => $userId,
			'read' => 0,
		))->count();
	}
	
	public function getList($userId, array $params = array())
	{		
		$searchParams = array();
		$searchParams['whereClause'] = array(
			'userId' => $userId
		);
		if (array_key_exists('offset', $params)) {
			$searchParams['offset'] = $params['offset'];
		}
		if (array_key_exists('maxSize', $params)) {
			$searchParams['limit'] = $params['maxSize'];
		}
		$searchParams['orderBy'] = 'createdAt';
		$searchParams['order'] = 'DESC';
				
		$collection = $this->getEntityManager()->getRepository('Notification')->find($searchParams);
		$count = $this->getEntityManager()->getRepository('Notification')->count($searchParams);
		
		$ids = array();
		foreach ($collection as $entity) {
			$ids[] = $entity->id;
			$data = json_decode($entity->get('data'));
			switch ($entity->get('type')) {
				case 'Note':				
					$note = $this->getEntityManager()->getEntity('Note', $data->noteId);
					if ($note->get('parentId') && $note->get('parentType')) {
						$parent = $this->getEntityManager()->getEntity($note->get('parentType'), $note->get('parentId'));
						if ($parent) {
							$note->set('parentName', $parent->get('name'));
						}
					}
					$entity->set('data', $note->toArray());
					break;
			}
		}		
		
		if (!empty($ids)) {
			$pdo = $this->getEntityManager()->getPDO();
			$sql = "UPDATE notification SET `read` = 1 WHERE id IN ('" . implode("', '", $ids) ."')";

			$s = $pdo->prepare($sql);
			$s->execute();
		}
		
		
		return array(
			'total' => $count,
			'collection' => $collection
		);
	}
}

