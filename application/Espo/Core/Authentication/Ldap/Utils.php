<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Authentication\Ldap;

use Espo\Core\Utils\Config;

class Utils
{
    private Config $config;

    /**
     * @var ?array<string, mixed>
     */
    private ?array $options = null;

    /**
     * @var array<string, string>
     */
    private $fieldMap = [
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
    ];

    /**
     * @var array<int, string>
     */
    private $permittedEspoOptions = [
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
    ];

    /**
     * AccountCanonicalForm Map between Espo and Laminas value.
     *
     *  @var array<string, int>
     */
    private $accountCanonicalFormMap = [
        'Dn' => 1,
        'Username' => 2,
        'Backslash' => 3,
        'Principal' => 4,
    ];

    public function __construct(?Config $config = null)
    {
        if (isset($config)) {
            $this->config = $config;
        }
    }

    /**
     * Get Options from espo config according to $this->fieldMap.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        if (isset($this->options)) {
            return $this->options;
        }

        $options = [];

        foreach ($this->fieldMap as $ldapName => $espoName) {
            $option = $this->config->get($espoName);

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
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function normalizeOptions(array $options): array
    {
        $useSsl = ($options['useSsl'] ?? null) == 'SSL';
        $useStartTls = ($options['useStartTls'] ?? null) == 'TLS';
        $accountCanonicalFormKey = $options['accountCanonicalForm'] ?? 'Dn';

        $options['useSsl'] = $useSsl;
        $options['useStartTls'] = $useStartTls;
        $options['accountCanonicalForm'] = $this->accountCanonicalFormMap[$accountCanonicalFormKey] ?? 1;

        return $options;
    }

    /**
     * Get an LDAP option.
     *
     * @param string $name
     * @param mixed $returns A default value.
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
     * Get Laminas options for using Laminas\Ldap.
     *
     * @return array<string, mixed>
     */
    public function getLdapClientOptions(): array
    {
        $options = $this->getOptions();

        return array_diff_key($options, array_flip($this->permittedEspoOptions));
    }
}
