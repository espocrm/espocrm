<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils\Authentication;
use Espo\Core\Exceptions\Error,
    Espo\Core\Utils\Config,
    Espo\Core\ORM\EntityManager,
    Espo\Core\Utils\Auth;

class LDAP extends Base
{
    private $utils;

    private $zendLdap;

    /**
     * Espo => LDAP name
     *
     * @var array
     */
    private $fields ;


    public function __construct(Config $config, EntityManager $entityManager, Auth $auth)
    {
        parent::__construct($config, $entityManager, $auth);
  
/*  
        
        $this->fields = array(
        'userName' => $parent.getConfig().get('ldapUserNameAttribute') ,
        'firstName' => $parent.getConfig().get('ldapUserFirstNameAttribute'),
        'lastName' => $parent.getConfig().get('ldapUserLastNameAttribute') ,
        'title' => $parent.getConfig().get('ldapUserTitleAttribute') ,
        'emailAddress' => $parent.getConfig().get('ldapUserEmailAddressAttribute') ,
        'phoneNumber' => $parent.getConfig().get('ldapUserPhoneNumberAttribute') ,
    );
    
 */

        $this->zendLdap = new LDAP\LDAP();
        $this->utils = new LDAP\Utils($config);
    }

    protected function getZendLdap()
    {
        return $this->zendLdap;
    }

    protected function getUtils()
    {
        return $this->utils;
    }


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

        $options = $this->getUtils()->getZendOptions();

        $ldap = $this->getZendLdap();
        $ldap = $ldap->setOptions($options);

        try {
            $ldap->bind($username, $password);

            $dn = $ldap->getDn($username);
            $GLOBALS['log']->debug('found DN for ['.$username.']: ['.$dn.']');


            $loginFilter = $this->getUtils()->getOption('userLoginFilter');
            $GLOBALS['log']->debug('found loginFilter: ['.$loginFilter.']');
            $userData = $ldap->searchByLoginFilter($loginFilter, $dn, 3);
            $GLOBALS['log']->debug('found userData ... ');

        } catch (\Zend\Ldap\Exception\LdapException $zle) {

            $admin = $this->adminLogin($username, $password);
            if (!isset($admin)) {
                $GLOBALS['log']->info('LDAP Authentication: ' . $zle->getMessage());
                return null;
            }

            $GLOBALS['log']->info('LDAP Authentication: Administrator login by username ['.$username.']');
        }

        $user = $this->getEntityManager()->getRepository('User')->findOne(array(
            'whereClause' => array(
                'userName' => $username,
            ),
        ));

        $isCreateUser = $this->getUtils()->getOption('createEspoUser');
        if (!isset($user) && $isCreateUser) {
            $this->getAuth()->useNoAuth(); /** Required to fix Acl "isFetched()" error */
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
     * Login user with administrator rights
     *
     * @param  string $username
     * @param  string $password
     * @return \Espo\Entities\User | null
     */
    protected function adminLogin($username, $password)
    {
        $hash = $this->getPasswordHash()->hash($password);

        $user = $this->getEntityManager()->getRepository('User')->findOne(array(
            'whereClause' => array(
                'userName' => $username,
                'password' => $hash,
                'isAdmin' => 1
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
        $GLOBALS['log']->info('Creating new user ...');
        $data = array();
        
        $this->fields = array(
            'userName' => $this.getConfig().get('ldapUserNameAttribute') ,
            'firstName' => $this.getConfig().get('ldapUserFirstNameAttribute'),
            'lastName' => $this.getConfig().get('ldapUserLastNameAttribute') ,
            'title' => $this.getConfig().get('ldapUserTitleAttribute') ,
            'emailAddress' => $this.getConfig().get('ldapUserEmailAddressAttribute') ,
            'phoneNumber' => $this.getConfig().get('ldapUserPhoneNumberAttribute') ,
        );

        // show full array of the LDAP user
        $GLOBALS['log']->debug(print_R($userData,TRUE));
        foreach ($this->fields as $espo => $ldap) {
            if (isset($userData[$ldap][0])) {
                $GLOBALS['log']->debug('    ['.$espo.']: ['.$userData[$ldap][0].']');
                $data[$espo] = $userData[$ldap][0];
            }
        }

        $user = $this->getEntityManager()->getEntity('User');
        $user->set($data);

        $this->getEntityManager()->saveEntity($user);

        return $user;
    }

}