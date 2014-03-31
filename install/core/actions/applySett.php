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
$result = array('success' => true, 'errorMsg' => '');

// save settings
$data = array(
	'driver' => 'pdo_mysql',
	'host' => $_SESSION['install']['host-name'],
	'dbname' => $_SESSION['install']['db-name'],
	'user' => $_SESSION['install']['db-user-name'],
	'password' => $_SESSION['install']['db-user-password'],
);
$lang = (!empty($_SESSION['install']['user-lang']))? $_SESSION['install']['user-lang'] : 'en_US';
if (!$installer->saveData($data, $lang)) {
	$result['success'] = false;
	$result['errorMsg'] = $langs['Can not save settings'];
}

ob_clean();
echo json_encode($result);