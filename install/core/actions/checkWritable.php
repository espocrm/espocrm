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

if (!$installer->isWritable()) {
	$result['success'] = false;
	$urls = $installer->getLastWritableError();
 	foreach ($urls as &$url) {
		$url = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$url;
 	}
	$result['errorMsg'] = $langs['messages']['Permission denied to files'].':<br>'.implode('<br>', $urls);
	$result['errorFixInstruction'] = $systemHelper->getPermissionCommands('', array('644', '755'));
}

ob_clean();
echo json_encode($result);