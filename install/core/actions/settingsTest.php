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

ob_start();

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