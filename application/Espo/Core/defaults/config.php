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
	'exportDelimiter' => ',',
	'currencyList' =>
	array (
	),
	'defaultCurrency' => 'USD',
	'currency' =>
	array(
		'base' => 'USD',
		'rate' => array(
		),
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
		'tr_TR',
		'ro_RO',
		'pt_BR'		
	),
	'language' => 'en_US',
	'logger' =>
	array (
		'path' => 'data/logs/espo.log',
		'level' => 'ERROR', /** DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY */
		'isRotate' => true, /** rotate log files every day */
		'maxRotateFiles' => 30, /** max number of rotate files */
	),
	'authenticationMethod' => 'Espo',
	'globalSearchEntityList' =>
	array (
		0 => 'Account',
		1 => 'Contact',
		2 => 'Lead',
		3 => 'Opportunity',
	),
	"tabList" => array("Account", "Contact", "Lead", "Opportunity", "Calendar", "Meeting", "Call", "Task", "Case", "Email"),
	"quickCreateList" => array("Account", "Contact", "Lead", "Opportunity", "Meeting", "Call", "Task", "Case"),
	'calendarDefaultEntity' => 'Meeting',
	'isInstalled' => false,
);

