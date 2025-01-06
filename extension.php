<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

/**
 * @deprecated Use `php command.php extension --file="path/to/extension/package.zip"`.
 */

if (!str_starts_with(php_sapi_name(), 'cli')) {
    exit;
}

include "bootstrap.php";

use Espo\Core\Application;
use Espo\Core\ApplicationRunners\Rebuild;
use Espo\Core\Upgrades\ExtensionManager;

$arg = isset($_SERVER['argv'][1]) ? trim($_SERVER['argv'][1]) : '';

if (empty($arg)) {
    die("Extension package file is not specified.\n");
}

if (!file_exists($arg)) {
    die("Package file does not exist.\n");
}

$pathInfo = pathinfo($arg);

if (!isset($pathInfo['extension']) || $pathInfo['extension'] !== 'zip' || !is_file($arg)) {
    die("Unsupported package.\n");
}

$app = new Application();
$app->setupSystemUser();

$config = $app->getContainer()->get('config');
$entityManager = $app->getContainer()->get('entityManager');

$upgradeManager = new ExtensionManager($app->getContainer());

echo "Starting installation process...\n";

try {
    $fileData = file_get_contents($arg);
    $fileData = 'data:application/zip;base64,' . base64_encode($fileData);

    $upgradeId = $upgradeManager->upload($fileData);
    $upgradeManager->install(array('id' => $upgradeId));
}
catch (\Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

try {
    (new Application())->run(Rebuild::class);
}
catch (\Exception $e) {}

echo "Extension installation is complete.\n";
