<?php

$result = array('success' => false, 'errorMsg' => '');

// create user
if (!empty($_SESSION['install']['user-name']) && !empty($_SESSION['install']['user-pass'])) {
	$userId = $installer->createUser($_SESSION['install']['user-name'], $_SESSION['install']['user-pass']);
	if (!empty($userId)) {
		$result['success'] = true;
	}
	else {
		$result['success'] = false;
		$result['errorMsg'] = 'Cannot create user';
	}
}
else {
	$result['success'] = false;
	$result['errorMsg'] = 'Cannot create user';
}

ob_clean();
echo json_encode($result);