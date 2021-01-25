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

namespace Espo\Tools\LayoutManager;

use Espo\Core\{
    Utils\File\Manager as FileManager,
    Utils\Layout,
    Utils\Util,
    Utils\Json,
};

class LayoutManager
{
    protected $fileManager;

    protected $layout;

    protected $changedData = [];

    public function __construct(FileManager $fileManager, Layout $layout)
    {
        $this->fileManager = $fileManager;
        $this->layout = $layout;
    }

    protected function sanitizeInput($name)
    {
        return preg_replace("([\.]{2,})", '', $name);
    }

    /**
     * Get layout in string format.
     */
    public function get(string $scope, string $name) : ?string
    {
        return $this->layout->get($scope, $name);
    }

    /**
     * Set layout data.
     */
    public function set($data, string $scope, string $name)
    {
        $scope = $this->sanitizeInput($scope);
        $name = $this->sanitizeInput($name);

        if (empty($scope) || empty($name)) {
            return false;
        }

        $this->changedData[$scope][$name] = $data;
    }

    public function resetToDefault(string $scope, string $name)
    {
        $scope = $this->sanitizeInput($scope);
        $name = $this->sanitizeInput($name);

        $filePath = 'custom/Espo/Custom/Resources/layouts/' . $scope . '/' . $name . '.json';

        if ($this->fileManager->isFile($filePath)) {
            $this->fileManager->removeFile($filePath);
        }

        if (!empty($this->changedData[$scope]) && !empty($this->changedData[$scope][$name])) {
            unset($this->changedData[$scope][$name]);
        }

        return $this->get($scope, $name);
    }

    /**
     * Save changes.
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
                    $layoutPath = $this->getDirPath($scope, true);
                    $data = Json::encode($layoutData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    $result &= $this->fileManager->putContents(array($layoutPath, $layoutName.'.json'), $data);
                }
            }
        }

        if ($result == true) {
            $this->clearChanges();
        }

        return (bool) $result;
    }

    /**
     * Clear unsaved changes.
     */
    public function clearChanges()
    {
        $this->changedData = [];
    }

    /**
     * Merge layout data.
     * Ex. $scope= Account, $name= detail then will be created a file layoutFolder/Account/detail.json
     */
    public function merge($data, string $scope, string $name)
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

    protected function getDirPath(string $entityType, bool $isCustom = false) : string
    {
        return $this->layout->getDirPath($entityType, $isCustom);
    }
}
