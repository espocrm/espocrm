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

namespace Espo\Tools\Layout;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Resource\FileReader;
use Espo\Core\Utils\Resource\FileReader\Params as FileReaderParams;
use RuntimeException;

class LayoutProvider
{
    private string $defaultPath = 'application/Espo/Resources/defaults/layouts';

    /** @internal Used by the portal layout util. */
    protected FileReader $fileReader;

    public function __construct(
        private FileManager $fileManager,
        private InjectableFactory $injectableFactory,
        private Metadata $metadata,
        FileReader $fileReader
    ) {
        $this->fileReader = $fileReader;
    }

    public function get(string $scope, string $name): ?string
    {
        if (
            $this->sanitizeInput($scope) !== $scope ||
            $this->sanitizeInput($name) !== $name
        ) {
            throw new RuntimeException("Bad parameters.");
        }

        $path = "layouts/$scope/$name.json";

        $params = FileReaderParams::create()->withScope($scope);

        $module = $this->getLayoutLocationModule($scope, $name);

        if ($module) {
            $params = $params
                ->withScope(null)
                ->withModuleName($module);
        }

        if ($this->fileReader->exists($path, $params)) {
            return $this->fileReader->read($path, $params);
        }

        $default = $this->getDefault($scope, $name);

        if ($default) {
            return $default;
        }

        foreach (array_reverse($this->metadata->getModuleList()) as $module) {
            $params = FileReaderParams::create()->withModuleName($module);

            if ($this->fileReader->exists($path, $params)) {
                return $this->fileReader->read($path, $params);
            }
        }

        return null;
    }

    private function getLayoutLocationModule(string $scope, string $name): ?string
    {
        return $this->metadata->get("app.layouts.$scope.$name.module");
    }

    private function getDefault(string $scope, string $name): ?string
    {
        $defaultImplClassName = 'Espo\\Custom\\Classes\\DefaultLayouts\\' . ucfirst($name) . 'Type';

        if (!class_exists($defaultImplClassName)) {
            $defaultImplClassName = 'Espo\\Classes\\DefaultLayouts\\' . ucfirst($name) . 'Type';
        }

        if (class_exists($defaultImplClassName)) {
            // @todo Use factory and interface.
            $defaultImpl = $this->injectableFactory->create($defaultImplClassName);

            if (!method_exists($defaultImpl, 'get')) {
                throw new RuntimeException("No 'get' method in '$defaultImplClassName'.");
            }

            $data = $defaultImpl->get($scope);

            return Json::encode($data);
        }

        $filePath = "$this->defaultPath/$name.json";

        if (!$this->fileManager->isFile($filePath)) {
            return null;
        }

        return $this->fileManager->getContents($filePath);
    }

    protected function sanitizeInput(string $name): string
    {
        /** @var string */
        return preg_replace("([.]{2,})", '', $name);
    }
}
