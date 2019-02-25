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

namespace Espo\Core\Utils;

class Autoload
{
    protected $data = null;

    private $config;

    private $metadata;

    private $fileManager;

    private $loader;

    protected $cacheFile = 'data/cache/application/autoload.php';

    protected $paths = array(
        'corePath' => 'application/Espo/Resources/autoload.json',
        'modulePath' => 'application/Espo/Modules/{*}/Resources/autoload.json',
        'customPath' => 'custom/Espo/Custom/Resources/autoload.json',
    );

    public function __construct(Config $config, Metadata $metadata, File\Manager $fileManager)
    {
        $this->config = $config;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->loader = new \Espo\Core\Utils\Autoload\Loader($config, $fileManager);
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getLoader()
    {
        return $this->loader;
    }

    public function get($key = null, $returns = null)
    {
        if (!isset($this->data)) {
            $this->init();
        }

        if (!isset($key)) {
            return $this->data;
        }

        return Utill::getValueByKey($this->data, $key, $returns);
    }

    public function getAll()
    {
        return $this->get();
    }

    protected function init()
    {
        if (file_exists($this->cacheFile) && $this->getConfig()->get('useCache')) {
            $this->data = $this->getFileManager()->getPhpContents($this->cacheFile);
            return;
        }

        $this->data = $this->unify();

        if ($this->getConfig()->get('useCache')) {
            $result = $this->getFileManager()->putPhpContents($this->cacheFile, $this->data);
            if ($result == false) {
                 throw new \Espo\Core\Exceptions\Error('Autoload: Cannot save unified autoload.');
            }
        }
    }

    protected function unify()
    {
        $data = $this->loadData($this->paths['corePath']);

        foreach ($this->getMetadata()->getModuleList() as $moduleName) {
            $modulePath = str_replace('{*}', $moduleName, $this->paths['modulePath']);
            $data = array_merge($data, $this->loadData($modulePath));
        }

        $data = array_merge($data, $this->loadData($this->paths['customPath']));

        return $data;
    }

    protected function loadData($autoloadFile, $returns = array())
    {
        if (file_exists($autoloadFile)) {
            $content = $this->getFileManager()->getContents($autoloadFile);
            $arrayContent = Json::getArrayData($content);
            if (!empty($arrayContent)) {
                return $this->normalizeData($arrayContent);
            }

            $GLOBALS['log']->error('Autoload::unify() - Empty file or syntax error - ['.$autoloadFile.']');
        }

        return $returns;
    }

    protected function normalizeData(array $data)
    {
        $normalizedData = [];

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'psr-4':
                case 'psr-0':
                case 'classmap':
                case 'files':
                case 'autoloadFileList':
                    $normalizedData[$key] = $value;
                    break;

                default:
                    $normalizedData['psr-0'][$key] = $value;
                    break;
            }
        }

        return $normalizedData;
    }

    public function register()
    {
        try {
            $autoloadList = $this->getAll();
        } catch (\Exception $e) {} //bad permissions

        if (!empty($autoloadList)) {
            $this->getLoader()->register($autoloadList);
        }
    }
}
