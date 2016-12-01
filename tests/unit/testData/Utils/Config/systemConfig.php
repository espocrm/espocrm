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

return array (
    'defaultPermissions' =>
    array (
        'dir' => '0775',
        'file' => '0664',
        'user' => '',
        'group' => '',
    ),
    'cron' => array(
        'maxJobNumber' => 15, /** Max number of jobs per one execution */
        'jobPeriod' => 7800, /** Period for jobs, ex. if cron executed at 15:35, it will execute all pending jobs for times from 14:05 to 15:35 */
        'minExecutionTime' => 50, /** to avoid too frequency execution **/
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
        'userItems',
    ),
    'adminItems' =>
    array (
        'devMode',
        'outboundEmailIsShared',
        'outboundEmailFromName',
        'outboundEmailFromAddress',
        'smtpServer',
        'smtpPort',
        'smtpAuth',
        'smtpSecurity',
        'smtpUsername',
        'smtpPassword',
        'cron',
    ),
    'userItems' =>
    array (
        'currencyList',
        'addressFormat',
        'quickCreateList',
        'recordsPerPage',
        'recordsPerPageSmall',
        'tabList',
        'thousandSeparator',
        'timeFormat',
        'timeZone',
        'weekStart'
    ),
    'isInstalled' => false,
);

