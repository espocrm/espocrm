<?php

namespace Espo\Core\Utils;

use \Espo\Core\Exceptions\Error;

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
		
		$user = $entityManager->getRepository('User')->get('system');		
		if (!$user) {
			throw new Error('System user is not found');			
		}

		$entityManager->setUser($user);
		$this->container->setUser($user);
	}
	
	public function login($username, $password)
	{
		$GLOBALS['log']->debug('AUTH: Try to authenticate');
		
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
			$GLOBALS['log']->debug('AUTH: Result of authenticate is [true]');
			return true;
		}
	}
}

