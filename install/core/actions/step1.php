<?php

$fields = array(
	'license-agree' => array(
		'default' => '0',
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

if (file_exists("LICENSE.txt")) {
	$license = file_get_contents('LICENSE.txt');
	$smarty->assign('license', $license);
}