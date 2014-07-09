<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

namespace Espo\Core\Utils\Authentication;

use \Espo\Core\Exceptions\Error;

class Ldap extends Base
{
	/**
	 * Espo => LDAP name
	 *
	 * @var array
	 */
	private $fields = array(
		'userName' => 'cn',
		'firstName' => 'givenname',
		'lastName' => 'sn',
		'title' => 'title',
		'emailAddress' => 'mail',
		'phoneNumber' => 'telephonenumber',
	);


	/**
	 * LDAP login
	 *
	 * @param  string $username
	 * @param  string $password
	 * @param  \Espo\Entities\AuthToken $authToken
	 * @return \Espo\Entities\User | null
	 */
	public function login($username, $password, \Espo\Entities\AuthToken $authToken = null)
	{
		if ($authToken) {
			return $this->loginByToken($username, $authToken);
		}

		$options = $this->getConfig()->get('ldap');
		$ldap = new Ldap\Ldap($options);

		try {
			$ldap->bind($username, $password);
			$ldapUsername = $ldap->getCanonicalAccountName($username);

		} catch (\Zend\Ldap\Exception\LdapException $zle) {
			$GLOBALS['log']->info('LDAP Authentication: ' . $zle->getMessage());
			return null;
		}

		$user = $this->getEntityManager()->getRepository('User')->findOne(array(
			'whereClause' => array(
				'userName' => $username,
			),
		));

		$isCreateUser = $ldap->getEspoOption('createEspoUser');
		if (!isset($user) && $isCreateUser) {

			$this->getAuth()->useNoAuth(); /** Required to fix Acl "isFetched()" error */

			$dn = $ldap->getDn($username);
			$userData = $ldap->getEntry($dn);
			$user = $this->createUser($userData);
		}

		return $user;
	}

	/**
	 * Login by authorization token
	 *
	 * @param  string $username
	 * @param  \Espo\Entities\AuthToken $authToken
	 * @return \Espo\Entities\User | null
	 */
	protected function loginByToken($username, \Espo\Entities\AuthToken $authToken = null)
	{
		if (!isset($authToken)) {
			return null;
		}

		$userId = $authToken->get('userId');
		$user = $this->getEntityManager()->getEntity('User', $userId);

		$tokenUsername = $user->get('userName');
		if ($username != $tokenUsername) {
			$GLOBALS['log']->alert('Unauthorized access attempt for user ['.$username.'] from IP ['.$_SERVER['REMOTE_ADDR'].']');
			return null;
		}

		$user = $this->getEntityManager()->getRepository('User')->findOne(array(
			'whereClause' => array(
				'userName' => $username,
			),
		));

		return $user;
	}

	/**
	 * Create Espo user with data gets from LDAP server
	 *
	 * @param  array $userData LDAP entity data
	 * @return \Espo\Entities\User
	 */
	protected function createUser(array $userData)
	{
		$data = array();
		foreach ($this->fields as $espo => $ldap) {
			if (isset($userData[$ldap][0])) {
				$data[$espo] = $userData[$ldap][0];
			}
		}

		$user = $this->getEntityManager()->getEntity('User');
		$user->set($data);

		$this->getEntityManager()->saveEntity($user);

		return $user;
	}

}

