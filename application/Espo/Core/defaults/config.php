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
 ************************************************************************/

return array (
    'database' =>
    array (
        'driver' => 'pdo_mysql',
        'host' => 'localhost',
        'port' => '',
        'dbname' => '',
        'user' => '',
        'password' => '',
    ),
    'useCache' => true,
    'recordsPerPage' => 20,
    'recordsPerPageSmall' => 5,
    'applicationName' => 'EspoCRM',
    'version' => '@@version',
    'timeZone' => 'UTC',
    'dateFormat' => 'MM/DD/YYYY',
    'timeFormat' => 'HH:mm',
    'weekStart' => 0,
    'thousandSeparator' => ',',
    'decimalMark' => '.',
    'exportDelimiter' => ';',
    'currencyList' =>
    array (
    ),
    'defaultCurrency' => 'USD',
    'baseCurrency' => 'USD',
    'currencyRates' => array(
    ),
    'outboundEmailIsShared' => true,
    'outboundEmailFromName' => 'EspoCRM',
    'outboundEmailFromAddress' => '',
    'smtpServer' => '',
    'smtpPort' => 25,
    'smtpAuth' => true,
    'smtpSecurity' => '',
    'smtpUsername' => '',
    'smtpPassword' => '',
    'languageList' => array(
        'en_US',
        'de_DE',
        'es_ES',
        'fr_FR',
        'nl_NL',
        'tr_TR',
        'ro_RO',
        'ru_RU',
        'pl_PL',
        'pt_BR',
        'uk_UA',
        'vi_VN'
    ),
    'language' => 'en_US',
    'logger' =>
    array (
        'path' => 'data/logs/espo.log',
        'level' => 'WARNING', /** DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY */
        'isRotate' => true, /** rotate log files every day */
        'maxRotateFiles' => 30, /** max number of rotate files */
    ),
    'authenticationMethod' => 'Espo',
    'globalSearchEntityList' =>
    array (
        'Account',
        'Contact',
        'Lead',
        'Opportunity',
    ),
    "tabList" => array("Account", "Contact", "Lead", "Opportunity", "Calendar", "Meeting", "Call", "Task", "Case", "Email", "Document", "Campaign"),
    "quickCreateList" => array("Account", "Contact", "Lead", "Opportunity", "Meeting", "Call", "Task", "Case"),
    'calendarDefaultEntity' => 'Meeting',
    'exportDisabled' => false,
    'assignmentEmailNotifications' => false,
    'assignmentEmailNotificationsEntityList' => array('Lead', 'Opportunity', 'Task', 'Case'),
    'assignmentNotificationsEntityList' => array('Meeting', 'Call', 'Task', 'Email'),
    'emailMessageMaxSize' => 10,
    'notificationsCheckInterval' => 10,
    'disabledCountQueryEntityList' => array('Email'),
    'maxEmailAccountCount' => 2,
    'followCreatedEntities' => false,
    'b2cMode' => false,
    'restrictedMode' => false,
    'theme' => 'Espo',
    'massEmailMaxPerHourCount' => 100,
    'personalEmailMaxPortionSize' => 10,
    'inboundEmailMaxPortionSize' => 20,
    'isInstalled' => false,
);

