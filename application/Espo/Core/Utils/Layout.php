<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class Layout
{
    private $fileManager;

    private $metadata;

    private $user;

    protected $changedData = [];

    protected $defaultsPath = 'application/Espo/Core/defaults';

    protected $paths = [
        'corePath' => 'application/Espo/Resources/layouts',
        'modulePath' => 'application/Espo/Modules/{*}/Resources/layouts',
        'customPath' => 'custom/Espo/Custom/Resources/layouts',
    ];

    public function __construct(\Espo\Core\Utils\File\Manager $fileManager, \Espo\Core\Utils\Metadata $metadata, \Espo\Entities\User $user)
    {
        $this->fileManager = $fileManager;
        $this->metadata = $metadata;
        $this->user = $user;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getUser()
    {
        return $this->user;
    }

    protected function sanitizeInput($name)
    {
        return preg_replace("([\.]{2,})", '', $name);
    }

    /**
     * Get layout in string format
     */
    public function get(string $scope, string $name) : ?string
    {
        $scope = $this->sanitizeInput($scope);
        $name = $this->sanitizeInput($name);

        if (isset($this->changedData[$scope][$name])) {
            return Json::encode($this->changedData[$scope][$name]);
        }

        $filePath = Util::concatPath($this->getLayoutPath($scope, true), $name . '.json');

        if (!file_exists($filePath))
            $filePath = Util::concatPath($this->getLayoutPath($scope), $name . '.json');

        if (!file_exists($filePath)) {
            $defaultImplClassName = '\\Espo\\Custom\\Core\\Utils\\Layout\\Defaults\\' . ucfirst($name) . 'Type';
            if (!class_exists($defaultImplClassName))
                $defaultImplClassName = '\\Espo\\Core\\Utils\\Layout\\Defaults\\' . ucfirst($name) . 'Type';

            if (class_exists($defaultImplClassName)) {
                $defaultImpl = new $defaultImplClassName($this->metadata);
                $data = $defaultImpl->get($scope);
                return Json::encode($data);
            }

            $defaultPath = $this->defaultsPath;
            $filePath = Util::concatPath(Util::concatPath($defaultPath, 'layouts'), $name . '.json' );

            if (!file_exists($filePath)) return null;
        }

        return $this->getFileManager()->getContents($filePath);
    }

    /**
     * Set Layout data
     * Ex. $scope = Account, $name = detail then will be created a file layoutFolder/Account/detail.json
     *
     * @param array $data
     * @param string $scope - ex. Account
     * @param string $name - detail
     *
     * @return void
     */
    public function set($data, $scope, $name)
    {
        $scope = $this->sanitizeInput($scope);
        $name = $this->sanitizeInput($name);

        if (empty($scope) || empty($name)) {
            return false;
        }

        $this->changedData[$scope][$name] = $data;
    }

    public function resetToDefault($scope, $name)
    {
        $scope = $this->sanitizeInput($scope);
        $name = $this->sanitizeInput($name);

        $filePath = 'custom/Espo/Custom/Resources/layouts/' . $scope . '/' . $name . '.json';
        if ($this->getFileManager()->isFile($filePath)) {
            $this->getFileManager()->removeFile($filePath);
        }
        if (!empty($this->changedData[$scope]) && !empty($this->changedData[$scope][$name])) {
            unset($this->changedData[$scope][$name]);
        }
        return $this->get($scope, $name);
    }

    /**
     * Save changes
     *
     * @return bool
     */
    public function save()
    {
        $result = true;

        if (!empty($this->changedData)) {
            foreach ($this->changedData as $scope => $rowData) {
                foreach ($rowData as $layoutName => $layoutData) {
                    if (empty($scope) || empty($layoutName)) {
                        continue;
                    }
                    $layoutPath = $this->getLayoutPath($scope, true);
                    $data = Json::encode($layoutData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    $result &= $this->getFileManager()->putContents(array($layoutPath, $layoutName.'.json'), $data);
                }
            }
        }

        if ($result == true) {
            $this->clearChanges();
        }

        return (bool) $result;
    }

    /**
     * Clear unsaved changes
     *
     * @return void
     */
    public function clearChanges()
    {
        $this->changedData = [];
    }

    /**
     * Merge layout data
     * Ex. $scope= Account, $name= detail then will be created a file layoutFolder/Account/detail.json
     *
     * @param JSON string $data
     * @param string $scope - ex. Account
     * @param string $name - detail
     *
     * @return bool
     */
    public function merge($data, $scope, $name)
    {
        $scope = $this->sanitizeInput($scope);
        $name = $this->sanitizeInput($name);

        $prevData = $this->get($scope, $name);

        $prevDataArray = Json::getArrayData($prevData);
        $dataArray = Json::getArrayData($data);

        $data = Util::merge($prevDataArray, $dataArray);
        $data = Json::encode($data);

        return $this->set($data, $scope, $name);
    }

    /**
     * Get Layout path, ex. application/Modules/Crm/Layouts/Account
     *
     * @param string $entityType
     * @param bool $isCustom - if need to check custom folder
     *
     * @return string
     */
    protected function getLayoutPath($entityType, $isCustom = false)
    {
        $path = $this->paths['customPath'];

        if (!$isCustom) {
            $moduleName = $this->getMetadata()->getScopeModuleName($entityType);

            $path = $this->paths['corePath'];
            if ($moduleName !== false) {
                $path = str_replace('{*}', $moduleName, $this->paths['modulePath']);
            }
        }

        $path = Util::concatPath($path, $entityType);

        return $path;
    }
}
