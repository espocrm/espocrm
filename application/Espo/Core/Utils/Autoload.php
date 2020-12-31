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

use Espo\Core\{
    Exceptions\Error,
    Utils\Autoload\Loader,
    Utils\DataCache,
    Utils\File\Manager as FileManager,
};

use Exception;

class Autoload
{
    protected $data = null;

    protected $cacheKey = 'autoload';

    protected $paths = [
        'corePath' => 'application/Espo/Resources/autoload.json',
        'modulePath' => 'application/Espo/Modules/{*}/Resources/autoload.json',
        'customPath' => 'custom/Espo/Custom/Resources/autoload.json',
    ];

    protected $config;
    protected $metadata;
    protected $dataCache;
    protected $fileManager;
    protected $loader;

    public function __construct(
        Config $config, Metadata $metadata, DataCache $dataCache, FileManager $fileManager, Loader $loader
    ) {
        $this->config = $config;
        $this->metadata = $metadata;
        $this->dataCache = $dataCache;
        $this->fileManager = $fileManager;
        $this->loader = $loader;
    }

    protected function getData() : array
    {
        if (!isset($this->data)) {
            $this->init();
        }

        return $this->data;
    }

    protected function init()
    {
        $useCache = $this->config->get('useCache');

        if ($useCache && $this->dataCache->has($this->cacheKey)) {
            $this->data = $this->dataCache->get($this->cacheKey);

            return;
        }

        $this->data = $this->loadData();

        if ($useCache) {
            $result = $this->dataCache->store($this->cacheKey, $this->data);
        }
    }

    protected function loadData() : array
    {
        $data = $this->loadDataFromFile($this->paths['corePath']);

        foreach ($this->metadata->getModuleList() as $moduleName) {
            $modulePath = str_replace('{*}', $moduleName, $this->paths['modulePath']);

            $data = array_merge_recursive($data, $this->loadDataFromFile($modulePath));
        }

        $data = array_merge_recursive($data, $this->loadDataFromFile($this->paths['customPath']));

        return $data;
    }

    protected function loadDataFromFile(string $filePath) : array
    {
        if (!$this->fileManager->isFile($filePath)) {
            return [];
        }

        $content = $this->fileManager->getContents($filePath);

        $arrayContent = Json::getArrayData($content);

        if (empty($arrayContent)) {
            $GLOBALS['log']->error("Autoload: Empty file or syntax error in '{$filePath}'.");

            return [];
        }

        return $this->normalizeData($arrayContent);
    }

    protected function normalizeData(array $data) : array
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
            $data = $this->getData();
        }
        catch (Exception $e) {} // bad permissions

        if (empty($data)) {
            return;
        }

        $this->loader->register($data);
    }
}
