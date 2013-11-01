<?php

namespace Espo\Entities;

use Doctrine\Common\Collections\ArrayCollection,
	\Espo\Core as Core;

/**
 * @Entity @Table(name="users")
 */
class User extends Core\Base
{
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $username;
	protected $password;
	protected $isAdmin;

	public function __construct()
	{
		//$this->reportedBugs = new ArrayCollection();
		//$this->assignedBugs = new ArrayCollection();
	}

	public function getId()
	{
		return $this->id;
	}

	public function getUsername()
	{
		return $this->username;
	}

	public function getIsAdmin()
	{
		return $this->isAdmin;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	*  Check user's credentials (NEED TO REWRITE)
	*/
	public function login($username, $password)
	{
        $dbUsername= 'admin';
        $dbPassword= '1';

		if ($username==$dbUsername && $password==$dbPassword) {
			return true;
		}

		return false;
	}

	//NEED TO REWRITE
    public function isAdmin($username='')
	{
    	if (empty($username)) {
        	$username= $this->getUsername();
    	}

		if ( !isset($this->id) ||  empty($this->id) ) {
			global $base;
			return $base->em->getRepository('\Espo\Entities\User')->findOneBy(array('username' => $username))->getIsAdmin(); //$this->getEntityManager()
		}

		if ($this->getUsername() == $username) {
        	return $this->getIsAdmin();
		}

        return false;
	}
}
