<?php

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

class Preferences extends \Espo\Core\Controllers\Base
{	
	protected function getPreferences()
	{
		return $this->getContainer()->get('preferences');
	}
	
	protected function getEntityManager()
	{
		return $this->getContainer()->get('entityManager');
	}

    public function actionRead($params)
	{
		$userId = $params['id'];
		if (!$this->getUser()->isAdmin()) {
			if ($this->getUser()->id != $userId) {
				throw new Forbidden();
			}
		}
		$entity =  $this->getEntityManager()->getEntity('Preferences', $userId);
		if ($entity) {
			return $entity->toArray();		
		}
		throw new NotFound();
	}

}

