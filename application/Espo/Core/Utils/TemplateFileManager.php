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
    Utils\Resource\FileReader,
    Utils\Resource\FileReader\Params as FileReaderParams,
};

class TemplateFileManager
{
    private $config;

    private $fileManager;

    private $fileReader;

    public function __construct(
        Config $config,
        FileManager $fileManager,
        FileReader $fileReader
    ) {
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->fileReader = $fileReader;
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

        $params = FileReaderParams::create()
            ->withScope($entityType)
            ->withModuleName($defaultModuleName);

        if ($entityType) {
            $path1 = $this->getPath($type, $name, $entityType);

            $exists1 = $this->fileReader->exists($path1, $params);

            if ($exists1) {
                return $this->fileReader->read($path1, $params);
            }
        }

        $path2 = $this->getPath($type, $name);

        $exists2 = $this->fileReader->exists($path2, $params);

        if ($exists2) {
            return $this->fileReader->read($path2, $params);
        }

        if ($entityType) {
            $path3 = $this->getDefaultLanguagePath($type, $name, $entityType);

            $exists3 = $this->fileReader->exists($path3, $params);

            if ($exists3) {
                return $this->fileReader->read($path3, $params);
            }
        }

        $path4 = $this->getDefaultLanguagePath($type, $name);

        return $this->fileReader->read($path4, $params);
    }

    public function saveTemplate(
        string $type,
        string $name,
        string $contents,
        ?string $entityType = null
    ): void {

        $language = $this->config->get('language');

        $filePath = $this->getCustomFilePath($language, $type, $name, $entityType);

        $this->fileManager->putContents($filePath, $contents);
    }

    public function resetTemplate(string $type, string $name, ?string $entityType = null): void
    {
        $language = $this->config->get('language');

        $filePath = $this->getCustomFilePath($language, $type, $name, $entityType);

        $this->fileManager->removeFile($filePath);
    }

    private function getCustomFilePath(
        string $language,
        string $type,
        string $name,
        ?string $entityType = null
    ): string {

        if ($entityType) {
            return "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
        }

        return "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$name}.tpl";
    }

    private function getPath(string $type, string $name, ?string $entityType = null): string
    {
        $language = $this->config->get('language');

        return $this->getPathForLanguage($language, $type, $name, $entityType);
    }

    private function getDefaultLanguagePath(string $type, string $name, ?string $entityType = null): string
    {
        $language = 'en_US';

        return $this->getPathForLanguage($language, $type, $name, $entityType);
    }

    private function getPathForLanguage(
        string $language,
        string $type,
        string $name,
        ?string $entityType = null
    ): string {

        if ($entityType) {
            return "templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
        }

        return "templates/{$type}/{$language}/{$name}.tpl";
    }
}
