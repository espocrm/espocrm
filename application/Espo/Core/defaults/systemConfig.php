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

return [
    'defaultPermissions' => [
        'dir' => '0775',
        'file' => '0664',
        'user' => '',
        'group' => ''
    ],
    'permissionMap' => [
        /** array('0664', '0775') */
        'writable' => [
            'data',
            'custom',
            'application/Espo/Modules',
            'client/modules'
        ],
        /** array('0644', '0755') */
        'readable' => [
            'api',
            'application',
            'client',
            'vendor',
            'index.php',
            'cron.php',
            'rebuild.php',
            'clear_cache.php'
        ],
    ],
    'jobMaxPortion' => 15, /** Max number of jobs per one execution. */
    'jobPeriod' => 7800, /** Max execution time (in seconds) allocated for a sinle job. If exceeded then set to Failed.*/
    'jobPeriodForActiveProcess' => 36000, /** Max execution time (in seconds) allocated for a sinle job with active process. If exceeded then set to Failed.*/
    'jobRerunAttemptNumber' => 1, /** Number of attempts to re-run failed jobs. */
    'jobRunInParallel' => false, /** Jobs will be executed in parallel processes. */
    'jobPoolConcurrencyNumber' => 8, /** Max number of processes run simultaneously. */
    'cronMinInterval' => 2, /** Min interval (in seconds) between two cron runs. */
    'daemonMaxProcessNumber' => 5, /** Max number of cron processes run simultaneously. */
    'daemonInterval' => 10, /** Interval between process runs in seconds. */
    'daemonProcessTimeout' => 36000,
    'crud' => [
        'get' => 'read',
        'post' => 'create',
        'put' => 'update',
        'patch' => 'patch',
        'delete' => 'delete',
    ],
    'systemUserAttributes' => [
        'id' => 'system',
        'userName' => 'system',
        'firstName' => '',
        'lastName' => 'System',
        'type' => 'system',
    ],
    'systemItems' => [
        'systemItems',
        'adminItems',
        'superAdminItems',
        'superAdminSystemItems',
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
        'apiSecretKeys',
        'restrictedMode',
        'userLimit',
        'portalUserLimit',
        'stylesheet',
        'userItems',
        'globalItems',
        'internalSmtpServer',
        'internalSmtpPort',
        'internalSmtpAuth',
        'internalSmtpUsername',
        'internalSmtpPassword',
        'internalSmtpSecurity',
        'internalOutboundEmailFromAddress',
        'requiredPhpVersion',
        'requiredMysqlVersion',
        'recommendedMysqlParams',
        'requiredPhpLibs',
        'recommendedPhpLibs',
        'recommendedPhpParams',
        'requiredMariadbVersion',
        'recommendedMariadbParams',
        'phpExecutablePath',
        'webSocketDebugMode',
        'webSocketSslCertificateFile',
        'webSocketSslCertificateLocalPrivateKey',
        'webSocketSslCertificatePassphrase',
        'webSocketSslAllowSelfSigned',
        'webSocketUseSecureServer',
        'webSocketPort',
    ],
    'adminItems' => [
        'devMode',
        'smtpServer',
        'smtpPort',
        'smtpAuth',
        'smtpSecurity',
        'smtpUsername',
        'smtpPassword',
        'jobMaxPortion',
        'jobPeriod',
        'jobRerunAttemptNumber',
        'jobRunInParallel',
        'jobPoolConcurrencyNumber',
        'jobPeriodForActiveProcess',
        'cronMinInterval',
        'daemonInterval',
        'daemonProcessTimeout',
        'daemonMaxProcessNumber',
        'authenticationMethod',
        'adminPanelIframeUrl',
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
        'ldapPortalUserLdapAuth',
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
        'authTokenMaxIdleTime',
        'ldapUserDefaultTeamId',
        'ldapUserDefaultTeamName',
        'ldapUserTeamsIds',
        'ldapUserTeamsNames',
        'ldapPortalUserPortalsIds',
        'ldapPortalUserPortalsNames',
        'ldapPortalUserRolesIds',
        'ldapPortalUserRolesNames',
        'cleanupJobPeriod',
        'cleanupActionHistoryPeriod',
        'adminNotifications',
        'adminNotificationsNewVersion',
        'adminNotificationsCronIsNotConfigured',
        'adminNotificationsNewExtensionVersion',
        'leadCaptureAllowOrigin',
        'cronDisabled',
        'defaultPortalId',
        'cleanupDeletedRecords',
        'authTokenPreventConcurrent',
        'emailParser',
        'latestVersion',
    ],
    'superAdminItems' => [
        'jobMaxPortion',
        'jobPeriod',
        'jobRerunAttemptNumber',
        'jobRunInParallel',
        'jobPoolConcurrencyNumber',
        'jobPeriodForActiveProcess',
        'cronMinInterval',
        'daemonInterval',
        'daemonProcessTimeout',
        'daemonMaxProcessNumber',
        'adminPanelIframeUrl',
        'cronDisabled',
        'maintenanceMode',
        'siteUrl',
        'useWebSocket',
        'webSocketUrl',
    ],
    'superAdminSystemItems' => [
    ],
    'userItems' => [

    ],
    'globalItems' => [
        'cacheTimestamp',
        'language',
        'isDeveloperMode',
        'dateFormat',
        'timeFormat',
        'timeZone',
        'decimalMark',
        'weekStart',
        'thousandSeparator',
        'companyLogoId',
        'applicationName',
        'jsLibs',
        'maintenanceMode',
        'siteUrl',
        'useCache',
        'useCacheInDeveloperMode',
        'isDeveloperMode',
        'version',
        'useWebSocket',
        'webSocketUrl',
        'aclAllowDeleteCreated',
    ],
    'isInstalled' => false,
    'ldapUserNameAttribute' => 'sAMAccountName',
    'ldapUserFirstNameAttribute' => 'givenName',
    'ldapUserLastNameAttribute' => 'sn',
    'ldapUserTitleAttribute' => 'title',
    'ldapUserEmailAddressAttribute' => 'mail',
    'ldapUserPhoneNumberAttribute' => 'telephoneNumber',
    'ldapUserObjectClass' => 'person',
    'requiredPhpVersion' => '7.1.0',
    'requiredPhpLibs' => [
        'json',
        'openssl',
        'pdo_mysql',
        'mbstring',
        'zip',
        'gd',
        'iconv'
    ],
    'recommendedPhpLibs' => [
        'curl',
        'xml',
        'xmlwriter',
        'exif',
    ],
    'recommendedPhpParams' => [
        'max_execution_time' => 180,
        'max_input_time' => 180,
        'memory_limit' => '256M',
        'post_max_size' => '20M',
        'upload_max_filesize' => '20M',
    ],
    'requiredMysqlVersion' => '5.5.3',
    'recommendedMysqlParams' => [],
    'requiredMariadbVersion' => '5.5.3',
    'recommendedMariadbParams' => [],
    'ldapPortalUserLdapAuth' => false,
    'aclStrictMode' => true,
];
