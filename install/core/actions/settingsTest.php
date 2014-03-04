<?php



$result = array('success' => true, 'errors' => array());

$res = $systemTest->checkRequirements();
$result['success'] &= $res['success'];
if (!empty($res['errors'])) {
	$result['errors'] = array_merge($result['errors'], $res['errors']);
}

if (!$systemTest->checkModRewrite()) {
	$result['success'] = false;
	$result['errors']['modRewrite'] = 'Enable mod_rewrite in Apache server';
}

if (!empty($_REQUEST['dbName']) && !empty($_REQUEST['hostName']) && !empty($_REQUEST['dbUserName'])) {
	$connect = false;
	
	$dbName = $_REQUEST['dbName'];
	$hostName = $_REQUEST['hostName'];
	$dbUserName = $_REQUEST['dbUserName'];
	$dbUserPass = $_REQUEST['dbUserPass'];
	$dbDriver = (!empty($_REQUEST['dbDriver']))? $_REQUEST['dbDriver'] : 'pdo_mysql';
	
	$res = $systemTest->checkDbConnection($hostName, $dbUserName, $dbUserPass, $dbName, $dbDriver);
	$result['success'] &= $res['success'];
	if (!empty($res['errors'])) {
		$result['errors'] = array_merge($result['errors'], $res['errors']);
	}
	
}

ob_clean();
echo json_encode($result);