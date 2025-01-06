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
    'errors' => [],
];

$phpRequiredList = $installer->getSystemRequirementList('php', true);

foreach ($phpRequiredList as $name => $details) {
    if (!$details['acceptable']) {

        switch ($details['type']) {
            case 'version':
                $result['success'] = false;
                $result['errors']['phpVersion'] = $details['required'];

                break;

            default:
                $result['success'] = false;
                $result['errors']['phpRequires'][] = $name;

                break;
        }
    }
}

$allPostData = $postData->getAll();

if (
    $result['success'] &&
    !empty($allPostData['dbName']) &&
    !empty($allPostData['hostName']) &&
    !empty($allPostData['dbUserName'])
) {
    $connect = false;

    $dbName = trim($allPostData['dbName']);

    if (!str_contains($allPostData['hostName'], ':')) {
        $allPostData['hostName'] .= ":";
    }

    [$hostName, $port] = explode(':', trim($allPostData['hostName']));

    $dbUserName = trim($allPostData['dbUserName']);
    $dbUserPass = trim($allPostData['dbUserPass']);

    if (!$port) {
        $port = null;
    }

    $platform = $allPostData['dbPlatform'] ?? 'Mysql';

    $databaseParams = [
        'platform' => $platform,
        'host' => $hostName,
        'port' => $port,
        'user' => $dbUserName,
        'password' => $dbUserPass,
        'dbname' => $dbName,
    ];

    $isConnected = true;

    try {
        $installer->checkDatabaseConnection($databaseParams, true);
    } catch (\Exception $e) {
        $isConnected = false;
        $result['success'] = false;
        $result['errors']['dbConnect']['errorCode'] = $e->getCode();
        $result['errors']['dbConnect']['errorMsg'] = $e->getMessage();
    }

    if ($isConnected) {
        $databaseRequiredList = $installer
            ->getSystemRequirementList('database', true, ['databaseParams' => $databaseParams]);

        foreach ($databaseRequiredList as $name => $details) {
            if (!$details['acceptable']) {
                switch ($details['type']) {
                    case 'version':
                        $result['success'] = false;
                        $result['errors'][$name] = $details['required'];

                        break;

                    default:
                        $result['success'] = false;
                        $result['errors'][$name][] = $name;

                        break;
                }
            }
        }
    }

}

ob_clean();
echo json_encode($result);
