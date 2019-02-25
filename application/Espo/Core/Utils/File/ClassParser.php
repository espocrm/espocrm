<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
use \Espo\Core\Utils\Util;

class ClassParser
{
    private $fileManager;

    private $config;

    private $metadata;

    protected $cacheFile = null;

    protected $allowedMethods = array(
        'run',
    );

    public function __construct(\Espo\Core\Utils\File\Manager $fileManager, \Espo\Core\Utils\Config $config, \Espo\Core\Utils\Metadata $metadata)
    {
        $this->fileManager = $fileManager;
        $this->config = $config;
        $this->metadata = $metadata;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    public function setAllowedMethods($methods)
    {
        $this->allowedMethods = $methods;
    }

    /**
     * Return path data of classes
     *
     * @param  string  $cacheFile full path for a cache file, ex. data/cache/application/entryPoints.php
     * @param  string | array $paths in format array(
     *    'corePath' => '',
     *    'modulePath' => '',
     *    'customPath' => '',
     * );
     * @return array
     */
    public function getData($paths, $cacheFile = false)
    {
        $data = null;

        if (is_string($paths)) {
            $paths = array(
                'corePath' => $paths,
            );
        }

        if ($cacheFile && file_exists($cacheFile) && $this->getConfig()->get('useCache')) {
            $data = $this->getFileManager()->getPhpContents($cacheFile);
        } else {
            $data = $this->getClassNameHash($paths['corePath']);

            if (isset($paths['modulePath'])) {
                foreach ($this->getMetadata()->getModuleList() as $moduleName) {
                    $path = str_replace('{*}', $moduleName, $paths['modulePath']);

                    $data = array_merge($data, $this->getClassNameHash($path));
                }
            }

            if (isset($paths['customPath'])) {
                $data = array_merge($data, $this->getClassNameHash($paths['customPath']));
            }

            if ($cacheFile && $this->getConfig()->get('useCache')) {
                $result = $this->getFileManager()->putPhpContents($cacheFile, $data);
                if ($result == false) {
                    throw new \Espo\Core\Exceptions\Error();
                }
            }
        }

        return $data;
    }

    protected function getClassNameHash($dirs)
    {
        if (is_string($dirs)) {
            $dirs = (array) $dirs;
        }

        $data = array();
        foreach ($dirs as $dir) {
            if (file_exists($dir)) {
                $fileList = $this->getFileManager()->getFileList($dir, false, '\.php$', true);

                foreach ($fileList as $file) {
                    $filePath = Util::concatPath($dir, $file);
                    $className = Util::getClassName($filePath);
                    $fileName = $this->getFileManager()->getFileName($filePath);

                    $scopeName = ucfirst($fileName);
                    $normalizedScopeName = Util::normilizeScopeName($scopeName);

                    if (empty($this->allowedMethods)) {
                        $data[$normalizedScopeName] = $className;
                        continue;
                    }

                    foreach ($this->allowedMethods as $methodName) {
                        if (method_exists($className, $methodName)) {
                            $data[$normalizedScopeName] = $className;
                        }
                    }

                }
            }
        }

        return $data;
    }

}