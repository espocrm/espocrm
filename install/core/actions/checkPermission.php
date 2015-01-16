<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
	ksort($group);
	$instruction = '';
	$instructionSU = '';
	$changeOwner = true;
	foreach($group as $permission => $folders) {
		if ($permission == '0644-0755') $folders = '';
		$instruction .= $systemHelper->getPermissionCommands(array($folders, ''), explode('-', $permission), false, null, $changeOwner) . "<br>";
		$instructionSU .= "&nbsp;&nbsp;" . $systemHelper->getPermissionCommands(array($folders, ''), explode('-', $permission), true, null, $changeOwner) . "<br>";
		if ($changeOwner) {
			$changeOwner = false;
		}
	}
	$result['errorMsg'] = $langs['messages']['Permission denied to'] . ':<br><pre>/'.implode('<br>/', $urls).'</pre>';
	$result['errorFixInstruction'] = str_replace( '"{C}"' , $instruction, $langs['messages']['permissionInstruction']) . "<br>" .
										str_replace( '{CSU}' , $instructionSU, $langs['messages']['operationNotPermitted']);
}

ob_clean();
echo json_encode($result);
