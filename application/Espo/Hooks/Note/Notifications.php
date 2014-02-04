<?php

namespace Espo\Hooks\Note;

use Espo\ORM\Entity;

class Notifications extends \Espo\Core\Hooks\Base
{
	protected $notificationService = null;
	
	protected function init()
	{
		$this->dependencies[] = 'serviceFactory';
	}
	
	protected function getServiceFactory()
	{
		return $this->getInjection('serviceFactory');
	}
	
	public function afterSave(Entity $entity)
	{
		if (!$entity->isFetched()) {

			$parentType = $entity->get('parentType');
			$parentId = $entity->get('parentId');			
		
			if ($parentType && $parentId) {
				$userIds = array();
				$pdo = $this->getEntityManager()->getPDO();
				$sql = "
					SELECT user_id AS userId 
					FROM subscription
					WHERE entity_id = " . $pdo->quote($parentId) . " AND entity_type = " . $pdo->quote($parentType);
				$sth = $pdo->prepare($sql);
				$sth->execute();
				while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
					if ($this->getUser()->id != $row['userId']) {
						$this->getNotificationService()->notifyAboutNote($row['userId'], $entity->id);
					}
				}
			}
		}
	}
	
	protected function getNotificationService()
	{
		if (empty($this->notificationService)) {
			$this->notificationService = $this->getServiceFactory()->create('Notification');
		}
		return $this->notificationService;		
	}
}

