<?php

namespace Espo\Core;

use \Espo\Core\Exceptions\Error;

class SelectManager
{	
	protected $container;
	
	protected $user;
	
	protected $acl;

    public function __construct(ORM\EntityManager $entityManager, \Espo\Entities\User $user, Acl $acl)
    {
    	$this->entityManager = $entityManager;
    	$this->user = $user;
    	$this->acl = $acl;
    }

	public function getSelectParams($entityName, array $params, $withAcl = false)
	{

	}
}
