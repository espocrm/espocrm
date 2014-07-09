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

namespace Espo\Core\Utils\Authentication\Ldap;

class Ldap extends \Zend\Ldap\Ldap
{
	protected $usernameAttribute = 'cn';

	protected $espoOptions = array();

	/**
	 * Permitted Espo Options
	 *
	 * @var array
	 */
	protected $permittedEspoOptions = array(
		/** Default Options (Zend LDAP):
		'host' => null,
		'port' => 0,
		'useSsl' => false,
		'username' => null,
		'password' => null,
		'bindRequiresDn' => false,
		'baseDn' => null,
		'accountCanonicalForm' => null,
		'accountDomainName' => null,
		'accountDomainNameShort' => null,
		'accountFilterFormat' => null,
		'allowEmptyPassword' => false,
		'useStartTls' => false,
		'optReferrals' => false,
		'tryUsernameSplit' => true,
		'networkTimeout' => null,*/

		/** Espo Options */
		'createEspoUser' => false,
	);


	public function setOptions($options)
	{
		$espoOptionList = array_keys($this->permittedEspoOptions);

		$this->espoOptions = array_intersect_key($options, array_flip($espoOptionList));
		$options = array_diff_key($options, array_flip($espoOptionList));

		return parent::setOptions($options);
	}

	/**
	 * Get Espo Options
	 *
	 * @param  string $name
	 * @param  mixed $returns
	 * @return mixed
	 */
	public function getEspoOption($name, $returns = null)
	{
		if (isset($this->espoOptions[$name])) {
			return $this->espoOptions[$name];
		}

		return $returns;
	}

	/**
	 * Get DN depends on options, ex. "cn=test,ou=People,dc=maxcrc,dc=com"
	 *
	 * @return string DN format
	 */
	public function getDn($acctname)
	{
		return $this->getAccountDn($acctname, \Zend\Ldap\Ldap::ACCTNAME_FORM_DN);
	}

	/**
	 * Fix a bug, ex. CN=Alice Baker,CN=Users,DC=example,DC=com
	 *
	 * @param  string $acctname
	 * @return string - Account DN
	 */
	protected function getAccountDn($acctname)
	{
		$baseDn = $this->getBaseDn();

		if ($this->getBindRequiresDn() && isset($baseDn)) {
			try {
				return parent::getAccountDn($acctname);
			} catch (\Zend\Ldap\Exception\LdapException $zle) {
				if ($zle->getCode() != \Zend\Ldap\Exception\LdapException::LDAP_NO_SUCH_OBJECT) {
					throw $zle;
				}
			}

			$acctname = $this->usernameAttribute . '=' . \Zend\Ldap\Filter\AbstractFilter::escapeValue($acctname) . ',' . $baseDn;
		}

		return parent::getAccountDn($acctname);
	}
}