<?php

namespace Espo\Core\Utils;

class User
{
	private $entityManager;
	private $config;

	private $currentUser;

	public function __construct(\Doctrine\ORM\EntityManager $entityManager, \Espo\Core\Utils\Config $config)
	{
		$this->entityManager = $entityManager;
		$this->config = $config;
	}

	protected function getEntityManager()
	{
    	return $this->entityManager;
	}

	protected function getConfig()
	{
    	return $this->config;
	}

	public function getCurrentUser()
	{
    	return $this->currentUser;
	}


    protected function setCurrentUser(\Espo\Entities\User $user)
	{
    	$this->currentUser = $user;
	}



	public function authenticate($username, $password)
	{
        $user = $this->getEntityManager()->getRepository('\Espo\Entities\User')->findOneBy(array('username' => $username));

		if ( $password == $user->getPassword() ) {
			$this->setCurrentUser($user);
			return true;
		}

		return false;
	}


    public function isAdmin(\Espo\Entities\User $user = null)
	{
    	if (is_null($user)) {
        	$user = $this->getCurrentUser();
    	} 

		if ($user instanceof \Espo\Entities\User) {
        	$id = $user->getId();
			if ( !empty($id) ) {
				return $user->getIsAdmin();
			}
		}

        return false;
	}  



}


?>