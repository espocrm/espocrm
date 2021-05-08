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
    Exceptions\Error,
};

class TemplateFileManager
{
    private $config;

    private $metadata;

    private $fileManager;

    public function __construct(Config $config, Metadata $metadata, FileManager $fileManager)
    {
        $this->config = $config;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
    }

    /**
     * @throws Error
     */
    public function getTemplate(
        string $type,
        string $name,
        ?string $entityType = null,
        ?string $defaultModuleName = null
    ): string {

        $fileName = $this->getTemplateFileName($type, $name, $entityType, $defaultModuleName);

        if (!$this->fileManager->isFile($fileName)) {
            throw new Error("Template file not found.");
        }

        $contents = file_get_contents($fileName);

        if ($contents === false) {
            throw new Error("Could not read template file.");
        }

        return $contents;
    }

    public function saveTemplate(
        string $type,
        string $name,
        string $contents,
        ?string $entityType = null
    ): void {

        $language = $this->config->get('language');

        if ($entityType) {
            $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
        }
        else {
            $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$name}.tpl";
        }

        $this->fileManager->putContents($fileName, $contents);
    }

    public function resetTemplate(string $type, string $name, ?string $entityType = null): void
    {
        $language = $this->config->get('language');

        if ($entityType) {
            $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
        }
        else {
            $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$name}.tpl";
        }

        $this->fileManager->removeFile($fileName);
    }

    private function getTemplateFileName(
        string $type,
        string $name,
        ?string $entityType = null,
        ?string $defaultModuleName = null
    ): ?string {

        $language = $this->config->get('language');

        if ($entityType) {
            $moduleName = $this->metadata->getScopeModuleName($entityType);

            $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";

            if ($this->fileManager->isFile($fileName)) {
                return $fileName;
            }

            if ($moduleName) {
                $fileName =
                    "application/Espo/Modules/{$moduleName}/Resources/" .
                    "templates/{$type}/{$language}/{$entityType}/{$name}.tpl";

                if ($this->fileManager->isFile($fileName)) {
                    return $fileName;
                }
            }

            $fileName = "application/Espo/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";

            if ($this->fileManager->isFile($fileName)) {
                return $fileName;
            }
        }

        $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$name}.tpl";

        if ($this->fileManager->isFile($fileName)) {
            return $fileName;
        }

        if ($defaultModuleName) {
            $fileName =
                "application/Espo/Modules/{$defaultModuleName}/" .
                "Resources/templates/{$type}/{$language}/{$name}.tpl";
        }
        else {
            $fileName = "application/Espo/Resources/templates/{$type}/{$language}/{$name}.tpl";
        }

        if ($this->fileManager->isFile($fileName)) {
            return $fileName;
        }

        $language = 'en_US';

        if ($entityType) {
            $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";

            if ($this->fileManager->isFile($fileName)) {
                return $fileName;
            }

            if ($moduleName) {
                $fileName =
                    "application/Espo/Modules/{$moduleName}/" .
                    "Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";

                if ($this->fileManager->isFile($fileName)) {
                    return $fileName;
                }
            }

            $fileName = "application/Espo/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";

            if ($this->fileManager->isFile($fileName)) {
                return $fileName;
            }
        }

        $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$name}.tpl";

        if ($this->fileManager->isFile($fileName)) {
            return $fileName;
        }

        if ($defaultModuleName) {
            $fileName =
                "application/Espo/Modules/{$defaultModuleName}/" .
                "Resources/templates/{$type}/{$language}/{$name}.tpl";
        }
        else {
            $fileName = "application/Espo/Resources/templates/{$type}/{$language}/{$name}.tpl";
        }

        return $fileName;
    }
}
