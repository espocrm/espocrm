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
    Utils\File\Manager as FileManager,
    Utils\Metadata,
};

class Layout
{
    protected $defaultPath = 'application/Espo/Resources/defaults/layouts';

    protected $paths = [
        'corePath' => 'application/Espo/Resources/layouts',
        'modulePath' => 'application/Espo/Modules/{*}/Resources/layouts',
        'customPath' => 'custom/Espo/Custom/Resources/layouts',
    ];

    protected $fileManager;

    protected $metadata;

    public function __construct(FileManager $fileManager, Metadata $metadata)
    {
        $this->fileManager = $fileManager;
        $this->metadata = $metadata;
    }

    protected function sanitizeInput(string $name) : string
    {
        return preg_replace("([\.]{2,})", '', $name);
    }


    public function get(string $scope, string $name) : ?string
    {
        $scope = $this->sanitizeInput($scope);
        $name = $this->sanitizeInput($name);

        if (isset($this->changedData[$scope][$name])) {
            return Json::encode($this->changedData[$scope][$name]);
        }

        $filePath = Util::concatPath(
            $this->getDirPath($scope, true),
            $name . '.json'
        );

        if (!file_exists($filePath)) {
            $filePath = Util::concatPath(
                $this->getDirPath($scope),
                $name . '.json'
            );
        }

        if (!file_exists($filePath)) {
            $defaultImplClassName = 'Espo\\Custom\\Classes\\DefaultLayouts\\' . ucfirst($name) . 'Type';

            if (!class_exists($defaultImplClassName)) {
                $defaultImplClassName = 'Espo\\Classes\\DefaultLayouts\\' . ucfirst($name) . 'Type';
            }

            if (class_exists($defaultImplClassName)) {
                $defaultImpl = new $defaultImplClassName($this->metadata);

                $data = $defaultImpl->get($scope);

                return Json::encode($data);
            }

            $filePath = Util::concatPath(
                $this->defaultPath,
                $name . '.json'
            );

            if (!file_exists($filePath)) {
                return null;
            }
        }

        return $this->fileManager->getContents($filePath);
    }

    public function getDirPath(string $entityType, bool $isCustom = false) : string
    {
        $path = $this->paths['customPath'];

        if (!$isCustom) {
            $moduleName = $this->metadata->getScopeModuleName($entityType);

            $path = $this->paths['corePath'];

            if ($moduleName !== false) {
                $path = str_replace('{*}', $moduleName, $this->paths['modulePath']);
            }
        }

        $path = Util::concatPath(
            Util::fixPath($path),
            $entityType
        );

        return $path;
    }
}
