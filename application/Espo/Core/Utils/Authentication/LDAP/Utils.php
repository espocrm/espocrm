<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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

namespace Espo\Core\Utils\Authentication\LDAP;
use \Espo\Core\Utils\Config;

class Utils
{
    private $config;

    protected $options = null;

    /**
     * Association between LDAP and Espo fields
     * @var array
     */
    protected $fieldMap = array(
        'host' => 'ldapHost',
        'port' => 'ldapPort',
        'useSsl' => 'ldapSecurity',
        'useStartTls' => 'ldapSecurity',
        'username' => 'ldapUsername',
        'password' => 'ldapPassword',
        'bindRequiresDn' => 'ldapBindRequiresDn',
        'baseDn' => 'ldapBaseDn',
        'accountCanonicalForm' => 'ldapAccountCanonicalForm',
        'accountDomainName' => 'ldapAccountDomainName',
        'accountDomainNameShort' => 'ldapAccountDomainNameShort',
        'accountFilterFormat' => 'ldapAccountFilterFormat',
        'optReferrals' => 'ldapOptReferrals',
        'tryUsernameSplit' => 'ldapTryUsernameSplit',
        'networkTimeout' => 'ldapNetworkTimeout',
        'createEspoUser' => 'ldapCreateEspoUser',
        'userNameAttribute' => 'ldapUserNameAttribute',
        'userTitleAttribute' => 'ldapUserTitleAttribute',
        'userFirstNameAttribute' => 'ldapUserFirstNameAttribute',
        'userLastNameAttribute' => 'ldapUserLastNameAttribute',
        'userEmailAddressAttribute' => 'ldapUserEmailAddressAttribute',
        'userPhoneNumberAttribute' => 'ldapUserPhoneNumberAttribute',
        'userLoginFilter' => 'ldapUserLoginFilter',
        'userTeamsIds' => 'ldapUserTeamsIds',
        'userDefaultTeamId' => 'ldapUserDefaultTeamId',
        'userObjectClass' => 'ldapUserObjectClass',
        'portalUserLdapAuth' => 'ldapPortalUserLdapAuth',
        'portalUserPortalsIds' => 'ldapPortalUserPortalsIds',
        'portalUserRolesIds' => 'ldapPortalUserRolesIds',
    );

    /**
     * Permitted Espo Options
     *
     * @var array
     */
    protected $permittedEspoOptions = array(
        'createEspoUser',
        'userNameAttribute',
        'userObjectClass',
        'userTitleAttribute',
        'userFirstNameAttribute',
        'userLastNameAttribute',
        'userEmailAddressAttribute',
        'userPhoneNumberAttribute',
        'userLoginFilter',
        'userTeamsIds',
        'userDefaultTeamId',
        'portalUserLdapAuth',
        'portalUserPortalsIds',
        'portalUserRolesIds',
    );

    /**
     * accountCanonicalForm Map between Espo and Zend value
     *
     * @var array
     */
    protected $accountCanonicalFormMap = array(
        'Dn' => 1,
        'Username' => 2,
        'Backslash' => 3,
        'Principal' => 4,
    );


    public function __construct(Config $config = null)
    {
        if (isset($config)) {
            $this->config = $config;
        }
    }

    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Get Options from espo config according to $this->fieldMap
     *
     * @return array
     */
    public function getOptions()
    {
        if (isset($this->options)) {
            return $this->options;
        }

        $options = array();
        foreach ($this->fieldMap as $ldapName => $espoName) {

            $option = $this->getConfig()->get($espoName);
            if (isset($option)) {
                $options[$ldapName] = $option;
            }
        }

        $this->options = $this->normalizeOptions($options);

        return $this->options;
    }

    /**
     * Normalize options to LDAP client format
     *
     * @param  array  $options
     *
     * @return array
     */
    public function normalizeOptions(array $options)
    {
        $options['useSsl'] = (bool) ($options['useSsl'] == 'SSL');
        $options['useStartTls'] = (bool) ($options['useStartTls'] == 'TLS');
        $options['accountCanonicalForm'] = $this->accountCanonicalFormMap[ $options['accountCanonicalForm'] ];

        return $options;
    }

    /**
     * Get an ldap option
     *
     * @param  string $name
     * @param  mixed $returns Return value
     * @return mixed
     */
    public function getOption($name, $returns = null)
    {
        if (!isset($this->options)) {
            $this->getOptions();
        }

        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        return $returns;
    }

    /**
     * Get Zend options for using Zend\Ldap
     *
     * @return array
     */
    public function getLdapClientOptions()
    {
        $options = $this->getOptions();
        $zendOptions = array_diff_key($options, array_flip($this->permittedEspoOptions));

        return $zendOptions;
    }

}
