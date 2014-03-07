<?php

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
