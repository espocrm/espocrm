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

$clearedCookieList = [
    'auth-token-secret',
    'auth-username',
    'auth-token',
];

foreach ($clearedCookieList as $cookieName) {
    if (!isset($_COOKIE[$cookieName])) {
        continue;
    }

    setcookie($cookieName, null, -1, '/');
}

$config = $installer->getConfig();

$fields = [
    'db-platform' => [
        'default' => $config->get('database.platform', 'Mysql'),
    ],
    'db-driver' => [
        'default' => $config->get('database.driver', ''),
    ],
    'db-name' => [
        'default' => $config->get('database.dbname', ''),
    ],
    'host-name' => [
        'default' => $config->get('database.host', '') .
            ($config->get('database.port') ? ':' . $config->get('database.port') : ''),
    ],
    'db-user-name' => [
        'default' => $config->get('database.user', ''),
    ],
    'db-user-password' => [],
];

foreach ($fields as $fieldName => $field) {
    if (isset($_SESSION['install'][$fieldName])) {
        $fields[$fieldName]['value'] = $_SESSION['install'][$fieldName];
    } else {
        $fields[$fieldName]['value'] = $field['default'] ?? '';
    }
}

$platforms = [
    'Mysql' => 'MySQL / MariaDB',
    'Postgresql' => 'PostgreSQL',
];

$smarty->assign('platforms', $platforms);

$smarty->assign('fields', $fields);
