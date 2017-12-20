<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

$sapiName = php_sapi_name();

if (substr($sapiName, 0, 3) != 'cli') {
    die("Extension installer script can be run only via CLI.\n");
}

include "bootstrap.php";

$arg = isset($_SERVER['argv'][1]) ? trim($_SERVER['argv'][1]) : '';

if (empty($arg)) {
    die("Specify extension package file.\n");
}

if (!file_exists($arg)) {
    die("Package file does not exist.\n");
}

$pathInfo = pathinfo($arg);
if (!isset($pathInfo['extension']) || $pathInfo['extension'] !== 'zip' || !is_file($arg)) {
    die("Unsupported package.\n");
}

$app = new \Espo\Core\Application();

$config = $app->getContainer()->get('config');
$entityManager = $app->getContainer()->get('entityManager');

$user = $entityManager->getEntity('User', 'system');
$app->getContainer()->setUser($user);

$upgradeManager = new \Espo\Core\ExtensionManager($app->getContainer());

echo "Start install process...\n";

try {
    $fileData = file_get_contents($arg);
    $fileData = 'data:application/zip;base64,' . base64_encode($fileData);

    $upgradeId = $upgradeManager->upload($fileData);
    $upgradeManager->install(array('id' => $upgradeId));
} catch (\Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

try {
    $app = new \Espo\Core\Application();
    $app->runRebuild();
} catch (\Exception $e) {}

echo "Installation is complete.\n";