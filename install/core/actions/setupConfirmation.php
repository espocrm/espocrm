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

$phpConfig = $systemHelper->getRecommendationList();
$smarty->assign('phpConfig', $phpConfig);

$installData = $_SESSION['install'];
list($host, $port) = explode(':', $installData['hostName']);

$dbConfig = array(
    'dbHostName' => $host,
    'dbPort' => $port,
    'dbName' => $installData['dbName'],
    'dbUserName' => $installData['dbUserName'],
    'dbUserPass' => $installData['dbUserPass'],
);
$mysqlConfig = $systemHelper->getRecommendationList('mysql', $dbConfig);

$dbConfig['dbHostName'] = $installData['hostName'];
unset($dbConfig['dbPort'], $dbConfig['dbUserPass']);

foreach ($dbConfig as $name => $value) {
	$mysqlConfig[$name] = array(
		'current' => $value,
		'acceptable' => true,
	);
}

$smarty->assign('mysqlConfig', $mysqlConfig);