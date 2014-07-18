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

namespace Espo\Core\Utils\Authentication\LDAP;

class LDAP extends \Zend\Ldap\Ldap
{
	protected $usernameAttribute = 'cn';


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

	/**
	 * Search a user using userLoginFilter
	 *
	 * @param  string $filter
	 * @param  string $basedn
	 * @param  int $scope
	 * @param  array  $attributes
	 * @return array
	 */
	public function searchByLoginFilter($filter, $basedn = null, $scope = self::SEARCH_SCOPE_SUB, array $attributes = array())
	{
		$filter = $this->getLoginFilter($filter);

		$result = $this->search($filter, $basedn, $scope, $attributes);

		if ($result->count() > 0) {
			return $result->getFirst();
		}

		throw new \Zend\Ldap\Exception\LdapException($this, 'searching: ' . $filter);
	}

	/**
	 * Get login filter in LDAP format
	 *
	 * @param  string $filter
	 * @return string
	 */
	protected function getLoginFilter($filter)
	{
		$baseFilter = '(objectClass=*)';

		if (!empty($filter)) {
			$baseFilter = '(&' . $baseFilter . $this->convertToFilterFormat($filter). ')';
		}

		return $baseFilter;
	}

	/**
	 * Check and convert filter item in LDAP format
	 *
	 * @param  string $filter [description]
	 * @return string
	 */
	protected function convertToFilterFormat($filter)
	{
		$filter = trim($filter);
		if (substr($filter, 0, 1) != '(') {
			$filter = '(' . $filter;
		}

		if (substr($filter, -1) != ')') {
			$filter = $filter . ')';
		}

		return $filter;
	}
}