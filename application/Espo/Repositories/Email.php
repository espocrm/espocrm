<?php

namespace Espo\Repositories;

use Espo\ORM\Entity;

class Email extends \Espo\Core\ORM\Repository
{
	protected function beforeSave(Entity $entity)
	{
		parent::beforeSave($entity);		
	}
}

