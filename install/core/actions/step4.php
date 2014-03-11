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

$fields = array(
	'dateFormat' =>array (
		'default' => (isset($settingsDefaults['dateFormat']['default'])) ? $settingsDefaults['dateFormat']['default'] : '',
		),
	'timeFormat' => array(
		'default'=> (isset($settingsDefaults['timeFormat']['default'])) ? $settingsDefaults['timeFormat']['default'] : ''),
	'timeZone' => array(),
	'weekStart' => array((isset($settingsDefaults['weekStart']['default'])) ? $settingsDefaults['weekStart']['default'] : ''),
	'defaultCurrency' => array(
		'default' => (isset($settingsDefaults['defaultCurrency']['default'])) ? $settingsDefaults['defaultCurrency']['default'] : ''),
	'thousandSeparator' => array(
		'default' => ',',
	),
	'decimalMark' =>array(
		'default' => '.',
	),
	'language' => array(
		'default'=> (!empty($_SESSION['install']['user-lang'])) ? $_SESSION['install']['user-lang'] : 'en_US'
		),
	'smtpServer' => array(),
	'smtpPort' => array(
		'default' => '25',
	),
	'smtpAuth' => array(),
	'smtpSecurity' => array(
		'default' => (isset($settingsDefaults['smtpSecurity']['default'])) ? $settingsDefaults['smtpSecurity']['default'] : ''),
	'smtpUsername' => array(),
	'smtpPassword' => array(),
	
	'outboundEmailFromName' => array(),
	'outboundEmailFromAddress' => array(),
	'outboundEmailIsShared' => array(),
);

foreach ($fields as $fieldName => $field) {
	if (isset($_SESSION['install'][$fieldName])) {
		$fields[$fieldName]['value'] = $_SESSION['install'][$fieldName];
	}
	else {
		$fields[$fieldName]['value'] = (isset($fields[$fieldName]['default']))? $fields[$fieldName]['default'] : '';
	}
}

$smarty->assign('fields', $fields);
