<?php

$result = array('success' => false, 'errors' => array());

if (!empty($_REQUEST['url'])) {
	if ($installer->fixAjaxPermission($_REQUEST['url'])) {
		$result['success'] = true;
	}
	else {
		$result['success'] = false;
		$result['errorMsg'] = $_REQUEST['url'];
	}
}

ob_clean();
echo json_encode($result);