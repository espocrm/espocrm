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

namespace Espo\Core\Utils\File;

use Espo\Core\{
    Exceptions\Error,
    Utils\Util,
    Utils\File\Manager as FileManager,
    Utils\Config,
    Utils\Metadata,
    Utils\DataCache,

};

use ReflectionClass;

class ClassParser
{
    private $fileManager;
    private $config;
    private $metadata;
    private $dataCache;

    public function __construct(FileManager $fileManager, Config $config, Metadata $metadata, DataCache $dataCache)
    {
        $this->fileManager = $fileManager;
        $this->config = $config;
        $this->metadata = $metadata;
        $this->dataCache = $dataCache;
    }

    /**
     * Return paths to class files.
     *
     * @param  string | array $paths in format [
     *    'corePath' => '',
     *    'modulePath' => '',
     *    'customPath' => '',
     * ]
     * @param $allowedMethods If specified, classes w/o specified method will be ignored.
     */
    public function getData($paths, ?string $cacheKey = null, ?array $allowedMethods = null, bool $subDirs = false) : array
    {
        $data = null;

        if (is_string($paths)) {
            $paths = [
                'corePath' => $paths,
            ];
        }

        if ($cacheKey && $this->dataCache->has($cacheKey) && $this->config->get('useCache')) {
            $data = $this->dataCache->get($cacheKey);

            if (!is_array($data)) {
                $GLOBALS['log']->error("ClassParser: Non-array value stored in {$cacheKey}.");
            }
        }

        if (!is_array($data)) {
            $data = $this->getClassNameHash($paths['corePath'], $allowedMethods, $subDirs);

            if (isset($paths['modulePath'])) {
                foreach ($this->metadata->getModuleList() as $moduleName) {
                    $path = str_replace('{*}', $moduleName, $paths['modulePath']);

                    $data = array_merge($data, $this->getClassNameHash($path, $allowedMethods, $subDirs));
                }
            }

            if (isset($paths['customPath'])) {
                $data = array_merge($data, $this->getClassNameHash($paths['customPath'], $allowedMethods, $subDirs));
            }

            if ($cacheKey && $this->config->get('useCache')) {
                $this->dataCache->store($cacheKey, $data);
            }
        }

        return $data;
    }

    protected function getClassNameHash($dirs, ?array $allowedMethods = [], bool $subDirs = false)
    {
        if (is_string($dirs)) {
            $dirs = (array) $dirs;
        }

        $data = [];

        foreach ($dirs as $dir) {
            if (file_exists($dir)) {
                $fileList = $this->fileManager->getFileList($dir, $subDirs, '\.php$', true);

                $this->fillHashFromFileList($fileList, $dir, $allowedMethods, $data);
            }
        }

        return $data;
    }

    protected function fillHashFromFileList(
        array $fileList, string $dir, ?array $allowedMethods, array &$data, string $category = ''
    ) {
        foreach ($fileList as $key => $file) {
            if (is_string($key)) {
                if (is_array($file)) {
                    $this->fillHashFromFileList($file, $dir . '/'. $key, $allowedMethods, $data, $category . $key . '\\');
                }

                continue;
            }

            $filePath = Util::concatPath($dir, $file);
            $className = Util::getClassName($filePath);

            $fileName = $this->fileManager->getFileName($filePath);

            $class = new ReflectionClass($className);

            if (!$class->isInstantiable()) {
                continue;
            }

            $name = Util::normilizeScopeName(ucfirst($fileName));

            $name = $category . $name;

            if (empty($allowedMethods)) {
                $data[$name] = $className;

                continue;
            }

            foreach ($allowedMethods as $methodName) {
                if (method_exists($className, $methodName)) {
                    $data[$name] = $className;
                }
            }
        }
    }
}
