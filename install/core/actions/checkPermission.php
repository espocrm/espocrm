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

if (!$installer->checkPermission()) {
	$result['success'] = false;
	$error = $installer->getLastPermissionError();
	$urls = array_keys($error);
	$group = array();
	foreach($error as $folder => $permission) {
		$group[implode('-', $permission)][] = $folder;
	}
	$instruction = '';
	$instructionSU = '';
	foreach($group as $permission => $folders) {
		$instruction .= $systemHelper->getPermissionCommands(array($folders, ''), explode('-', $permission)) . ";<br>";
		$instructionSU .= "&nbsp;&nbsp;" . $systemHelper->getPermissionCommands(array($folders, ''), explode('-', $permission), true) . ";<br>";
	}
	$result['errorMsg'] = $langs['messages']['Permission denied to'] . ':<br><pre>/'.implode('<br>/', $urls).'</pre>';
	$result['errorFixInstruction'] = str_replace( '"{C}"' , $instruction, $langs['messages']['permissionInstruction']) . "<br>" .
										str_replace( '{CSU}' , $instructionSU, $langs['messages']['operationNotPermitted']);
}

ob_clean();
echo json_encode($result);
