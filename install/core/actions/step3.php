<?php

$fields = array(
	'user-name' => array(
		'default' => (isset($langs['admin']))? $langs['admin'] : 'admin',
	),
	'user-pass' => array(),
	'user-confirm-pass' => array(),
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