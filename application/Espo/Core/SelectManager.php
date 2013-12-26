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
		$result = array();
		
		if (!empty($params['sortBy'])) {
			$result['orderBy'] = $params['sortBy'];
		}
		if (isset($params['asc'])) {
			if ($params['asc']) {
				$result['order'] = 'ASC';
			} else {
				$result['order'] = 'DESC';
			}
		}
		
		if (isset($params['offset']) && !is_null($params['offset'])) {
			$result['offset'] = $params['offset'];
		}
		if (isset($params['maxSize']) && !is_null($params['maxSize'])) {
			$result['limit'] = $params['maxSize'];
		}
		
		return $result;
	}
}
