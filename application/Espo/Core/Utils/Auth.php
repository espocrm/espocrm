<?php

namespace Espo\Core\Utils;

class Auth 
{
	protected $container;
	
	public function __construct(\Espo\Core\Container $container)
	{
		$this->container = $container;
	}
	
	public function useNoAuth()
	{
		$entityManager = $this->container->get('entityManager');		
		$this->container->setUser($entityManager->getEntity('User'));
	}
	
	public function login($username, $password)
	{
		$GLOBALS['log']->add('DEBUG', 'AUTH: Try to authenticate');
		
		$entityManager = $this->container->get('entityManager');
		
		$user = $entityManager->getRepository('User')->findOne(array(
			'whereClause' => array(
				'userName' => $username,
				'password' => md5($password)
			),
		));
		
		if ($user instanceof \Espo\Entities\User) {
			$entityManager->setUser($user);
			$this->container->setUser($user);
			$GLOBALS['log']->add('DEBUG', 'AUTH: Result of authenticate =[' . $isAuthenticated . ']');
			return true;
		}
	}
}

