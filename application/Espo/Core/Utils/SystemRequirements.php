<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
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

namespace Espo\Core\Utils;

use Espo\Core\{
    Utils\Config,
    Utils\File\Manager as FileManager,
    Utils\System,
    Utils\Database\Helper as DatabaseHelper,
};

use PDO;

class SystemRequirements
{
    private $config;

    private $fileManager;

    private $systemHelper;

    private $databaseHelper;

    public function __construct(
        Config $config,
        FileManager $fileManager,
        System $systemHelper,
        DatabaseHelper $databaseHelper
    ) {
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->systemHelper = $systemHelper;
        $this->databaseHelper = $databaseHelper;
    }

    public function getAllRequiredList(bool $requiredOnly = false): array
    {
        return [
            'php' => $this->getPhpRequiredList($requiredOnly),
            'database' => $this->getDatabaseRequiredList($requiredOnly),
            'permission' => $this->getRequiredPermissionList($requiredOnly),
        ];
    }

    public function getRequiredListByType(
        string $type,
        bool $requiredOnly = false,
        array $additionalData = null
    ): array {

        switch ($type) {
            case 'php':
                return $this->getPhpRequiredList($requiredOnly, $additionalData);

            case 'database':
                return $this->getDatabaseRequiredList($requiredOnly, $additionalData);

            case 'permission':
                return $this->getRequiredPermissionList($requiredOnly, $additionalData);
        }

        return [];
    }

    /**
     * Get required PHP params.
     */
    protected function getPhpRequiredList(bool $requiredOnly, array $additionalData = null): array
    {
        $requiredList = [
            'requiredPhpVersion',
            'requiredPhpLibs',
        ];

        if (!$requiredOnly) {
            $requiredList = array_merge($requiredList, [
                'recommendedPhpLibs',
                'recommendedPhpParams',
            ]);
        }

        return $this->getRequiredList('phpRequirements', $requiredList, $additionalData);
    }

    /**
     * Get required DB params.
     */
    protected function getDatabaseRequiredList(bool $requiredOnly, array $additionalData = null): array
    {
        $databaseTypeName = 'Mysql';

        $databaseHelper =  $this->databaseHelper;
        $databaseParams = $additionalData['database'] ?? [];

        $pdoConnection = $databaseHelper->createPdoConnection($databaseParams);

        if ($pdoConnection) {
            $databaseHelper->setPdoConnection($pdoConnection);
            $databaseType = $databaseHelper->getDatabaseType();
            $databaseTypeName = ucfirst(strtolower($databaseType));
        }

        $requiredList = [
            'required' . $databaseTypeName . 'Version',
        ];

        if (!$requiredOnly) {
            $requiredList = array_merge($requiredList, [
                'recommended' . $databaseTypeName . 'Params',
                'connection',
            ]);
        }

        return $this->getRequiredList('databaseRequirements', $requiredList, $additionalData);
    }

    /**
     * Get permission requirements.
     */
    private function getRequiredPermissionList(bool $requiredOnly, array $additionalData = null): array
    {
        return $this->getRequiredList(
            'permissionRequirements',
            [
                'permissionMap.writable',
            ],
            $additionalData, [
                'permissionMap.writable' => $this->fileManager->getPermissionUtils()->getWritableList(),
            ]
        );
    }

    private function getRequiredList(
        string $type,
        array $checkList,
        array $additionalData = null,
        array $predefinedData = []
    ): array {

        $list = [];

        foreach ($checkList as $itemName) {
            $methodName = 'check' . ucfirst($type);

            if (method_exists($this, $methodName)) {
                $itemValue =
                    isset($predefinedData[$itemName]) ?
                    $predefinedData[$itemName] :
                    $this->config->get($itemName);

                $result = $this->$methodName($itemName, $itemValue, $additionalData);
                $list = array_merge($list, $result);
            }
        }

        return $list;
    }

    /**
     * Check PHP requirements,
     */
    private function checkPhpRequirements(string $type, $data, array $additionalData = null): array
    {
        $list = [];

        switch ($type) {
            case 'requiredPhpVersion':
                $actualVersion = $this->systemHelper->getPhpVersion();
                $requiredVersion = $data;

                $acceptable = true;

                if (version_compare($actualVersion, $requiredVersion) == -1) {
                    $acceptable = false;
                }

                $list[$type] = [
                    'type' => 'version',
                    'acceptable' => $acceptable,
                    'required' => $requiredVersion,
                    'actual' => $actualVersion,
                ];

                break;

            case 'requiredPhpLibs':
            case 'recommendedPhpLibs':
                foreach ($data as $name) {
                    $acceptable = $this->systemHelper->hasPhpExtension($name);

                    $list[$name] = array(
                        'type' => 'lib',
                        'acceptable' => $acceptable,
                        'actual' => $acceptable ? 'On' : 'Off',
                    );
                }
                break;

            case 'recommendedPhpParams':
                foreach ($data as $name => $value) {
                    $requiredValue = $value;
                    $actualValue = $this->systemHelper->getPhpParam($name);

                    $acceptable = (
                        Util::convertToByte($actualValue) >= Util::convertToByte($requiredValue)
                    ) ? true : false;

                    $list[$name] = [
                        'type' => 'param',
                        'acceptable' => $acceptable,
                        'required' => $requiredValue,
                        'actual' => $actualValue,
                    ];
                }

                break;
        }

        return $list;
    }

    /**
     * Check DB requirements.
     */
    private function checkDatabaseRequirements(string $type, $data, array $additionalData = null): array
    {
        $list = [];

        $databaseHelper = $this->databaseHelper;

        $databaseParams = $additionalData['database'] ?? [];

        $pdo = $databaseHelper->createPdoConnection($databaseParams);

        if (!$pdo) {
            $type = 'connection';
        }

        switch ($type) {
            case 'requiredMysqlVersion':
            case 'requiredMariadbVersion':
                $actualVersion = $databaseHelper->getPdoDatabaseVersion($pdo);
                $requiredVersion = $data;

                $acceptable = true;
                if (version_compare($actualVersion, $requiredVersion) == -1) {
                    $acceptable = false;
                }

                $list[$type] = [
                    'type' => 'version',
                    'acceptable' => $acceptable,
                    'required' => $requiredVersion,
                    'actual' => $actualVersion,
                ];
                break;

            case 'recommendedMysqlParams':
            case 'recommendedMariadbParams':
                foreach ($data as $name => $value) {
                    $requiredValue = $value;
                    $actualValue = $databaseHelper->getPdoDatabaseParam($name, $pdo);

                    $acceptable = false;

                    switch (gettype($requiredValue)) {
                        case 'integer':
                            if (Util::convertToByte($actualValue) >= Util::convertToByte($requiredValue)) {
                                $acceptable = true;
                            }

                            break;

                        case 'string':
                            if (strtoupper($actualValue) === strtoupper($requiredValue)) {
                                $acceptable = true;
                            }

                            break;
                    }

                    $list[$name] = [
                        'type' => 'param',
                        'acceptable' => $acceptable,
                        'required' => $requiredValue,
                        'actual' => $actualValue,
                    ];
                }

                break;

            case 'connection':
                    if (!$databaseParams) {
                        $databaseParams = $this->config->get('database');
                    }

                    $acceptable = true;

                    if (!$pdo instanceof PDO) {
                        $acceptable = false;
                    }

                    $list['host'] = [
                        'type' => 'connection',
                        'acceptable' => $acceptable,
                        'actual' => $databaseParams['host'],
                    ];
                    $list['dbname'] = [
                        'type' => 'connection',
                        'acceptable' => $acceptable,
                        'actual' => $databaseParams['dbname'],
                    ];
                    $list['user'] = [
                        'type' => 'connection',
                        'acceptable' => $acceptable,
                        'actual' => $databaseParams['user'],
                    ];

                    break;
        }

        return $list;
    }

    private function checkPermissionRequirements(string $type, $data, array $additionalData = null): array
    {
        $list = [];

        $fileManager = $this->fileManager;

        switch ($type) {
            case 'permissionMap.writable':
                foreach ($data as $item) {
                    $fullPathItem = Util::concatPath($this->systemHelper->getRootDir(), $item);
                    $list[$fullPathItem] = [
                        'type' => 'writable',
                        'acceptable' => $fileManager->isWritable($fullPathItem) ? true : false,
                    ];
                }
                break;

            case 'permissionMap.readable':
                foreach ($data as $item) {
                    $fullPathItem = Util::concatPath($this->systemHelper->getRootDir(), $item);
                    $list[$fullPathItem] = [
                        'type' => 'readable',
                        'acceptable' => $fileManager->isReadable($fullPathItem) ? true : false,
                    ];
                }
                break;
        }

        return $list;
    }
}
