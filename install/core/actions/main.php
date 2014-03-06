<?php

$fields = array(
	'user-lang' => array(
		'default' => (isset($settingsDefaults['language']['default'])) ? $settingsDefaults['language']['default'] : 'en_US',
	),
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
