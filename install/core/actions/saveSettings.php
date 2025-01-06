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

ob_start();

$result = [
    'success' => true,
    'errorMsg' => '',
];

// save settings
$database = [
    'dbname' => $_SESSION['install']['db-name'],
    'user' => $_SESSION['install']['db-user-name'],
    'password' => $_SESSION['install']['db-user-password'],
    'platform' => $_SESSION['install']['db-platform'] ?? 'Mysql',
];

$host = $_SESSION['install']['host-name'];

if (!str_contains($host, ':')) {
    $host .= ":";
}

[$database['host'], $database['port']] = explode(':', $host);

$saveData = [
    'database' => $database,
    'language' => !empty($_SESSION['install']['user-lang']) ? $_SESSION['install']['user-lang'] : 'en_US',
    'siteUrl' => !empty($_SESSION['install']['site-url']) ? $_SESSION['install']['site-url'] : null,
];

if (!empty($_SESSION['install']['theme'])) {
    $saveData['theme'] = $_SESSION['install']['theme'];
}

if (!empty($_SESSION['install']['default-permissions-user']) && !empty($_SESSION['install']['default-permissions-group'])) {
    $saveData['defaultPermissions'] = [
        'user' => $_SESSION['install']['default-permissions-user'],
        'group' => $_SESSION['install']['default-permissions-group'],
    ];
}

if (!$installer->saveData($saveData)) {
    $result['success'] = false;
    $result['errorMsg'] = $langs['messages']['Can not save settings'];
}

ob_clean();
echo json_encode($result);
