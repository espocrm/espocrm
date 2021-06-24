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
    Utils\Json,
    Exceptions\Error,
};

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;

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

    /**
     * Get layout in string format.
     */
    public function get(string $scope, string $name): ?string
    {
        return $this->layout->get($scope, $name);
    }

    /**
     * Set layout data.
     *
     * @param mixed $data
     */
    public function set($data, string $scope, string $name): void
    {
        $scope = $this->sanitizeInput($scope);
        $name = $this->sanitizeInput($name);

        if (empty($scope) || empty($name)) {
            throw new Error("Error while setting layout.");
        }

        $this->changedData[$scope][$name] = $data;
    }

    public function resetToDefault(string $scope, string $name): ?string
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
     *
     * @throws Error
     */
    public function save(): void
    {
        $result = true;

        if (empty($this->changedData)) {
            return;
        }

        foreach ($this->changedData as $scope => $rowData) {
            $dirPath = 'custom/Espo/Custom/Resources/layouts/' . $scope . '/';

            foreach ($rowData as $layoutName => $layoutData) {
                if (empty($scope) || empty($layoutName)) {
                    continue;
                }

                $data = Json::encode($layoutData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                $path = $dirPath . $layoutName . '.json';

                $result &= $this->fileManager->putContents($path, $data);
            }
        }

        if (!$result) {
            throw new Error("Error while saving layout.");
        }

        $this->clearChanges();
    }

    /**
     * Clear unsaved changes.
     */
    public function clearChanges(): void
    {
        $this->changedData = [];
    }

    protected function sanitizeInput(string $name): string
    {
        return preg_replace("([\.]{2,})", '', $name);
    }
}
