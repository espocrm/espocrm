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

return array (    'defaultPermissions' =>
    array (
        'dir' => '0775',
        'file' => '0664',
        'user' => '',
        'group' => '',
    ),

    'permissionMap' => array(

        /** array('0664', '0775') */
        'writable' => array(
            'data',
            'custom',
        ),

        /** array('0644', '0755') */
        'readable' => array(
            'api',
            'application',
            'client',
            'vendor',
            'index.php',
            'cron.php',
            'rebuild.php',
            'main.html',
            'reset.html',
        ),
    ),
    'cron' => array(
        'maxJobNumber' => 15, /** Max number of jobs per one execution */
        'jobPeriod' => 7800, /** Period for jobs, ex. if cron executed at 15:35, it will execute all pending jobs for times from 14:05 to 15:35 */
        'minExecutionTime' => 50, /** to avoid too frequency execution */
        'attempts' => 3, /** attempts to run jobs */
    ),
    'crud' => array(
        'get' => 'read',
        'post' => 'create',
        'put' => 'update',
        'patch' => 'patch',
        'delete' => 'delete',
    ),
    'systemUser' => array(
        'id' => 'system',
        'userName' => 'system',
        'firstName' => '',
        'lastName' => 'System',
    ),
    'systemItems' =>
    array (
        'systemItems',
        'adminItems',
        'configPath',
        'cachePath',
        'database',
        'crud',
        'logger',
        'isInstalled',
        'defaultPermissions',
        'systemUser',
        'permissionMap',
        'permissionRules',
        'passwordSalt',
        'cryptKey',
        'restrictedMode',
        'userLimit',
        'portalUserLimit',
        'stylesheet'
    ),
    'adminItems' =>
    array (
        'devMode',
        'smtpServer',
        'smtpPort',
        'smtpAuth',
        'smtpSecurity',
        'smtpUsername',
        'smtpPassword',
        'cron',
        'authenticationMethod',
        'ldapHost',
        'ldapPort',
        'ldapSecurity',
        'ldapAuth',
        'ldapUsername',
        'ldapPassword',
        'ldapBindRequiresDn',
        'ldapBaseDn',
        'ldapUserLoginFilter',
        'ldapAccountCanonicalForm',
        'ldapAccountDomainName',
        'ldapAccountDomainNameShort',
        'ldapAccountFilterFormat',
        'ldapTryUsernameSplit',
        'ldapOptReferrals',
        'ldapCreateEspoUser',
        'ldapAccountDomainName',
        'ldapAccountDomainNameShort',
        'ldapUserNameAttribute',
        'ldapUserFirstNameAttribute',
        'ldapUserLastNameAttribute',
        'ldapUserTitleAttribute',
        'ldapUserEmailAddressAttribute',
        'ldapUserPhoneNumberAttribute',
        'ldapUserObjectClass',
        'maxEmailAccountCount',
        'massEmailMaxPerHourCount',
        'personalEmailMaxPortionSize',
        'inboundEmailMaxPortionSize',
        'authTokenLifetime',
        'authTokenMaxIdleTime'
    ),
    'isInstalled' => false,
    'ldapUserNameAttribute' => 'sAMAccountName',
    'ldapUserFirstNameAttribute' => 'givenName',
    'ldapUserLastNameAttribute' => 'sn',
    'ldapUserTitleAttribute' => 'title',
    'ldapUserEmailAddressAttribute' => 'mail',
    'ldapUserPhoneNumberAttribute' => 'telephoneNumber',
    'ldapUserObjectClass' => 'person',
);

