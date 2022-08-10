<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
    Utils\Autoload\Loader,
    Utils\DataCache,
    Utils\File\Manager as FileManager,
    Utils\Resource\PathProvider,
};

use Exception;

class Autoload
{
    /**
     * @var ?array<string,mixed>
     */
    private $data = null;

    private string $cacheKey = 'autoload';

    private string $autoloadFileName = 'autoload.json';

    private Config $config;

    private Metadata $metadata;

    private DataCache $dataCache;

    private FileManager $fileManager;

    private Loader $loader;

    private PathProvider $pathProvider;

    public function __construct(
        Config $config,
        Metadata $metadata,
        DataCache $dataCache,
        FileManager $fileManager,
        Loader $loader,
        PathProvider $pathProvider
    ) {
        $this->config = $config;
        $this->metadata = $metadata;
        $this->dataCache = $dataCache;
        $this->fileManager = $fileManager;
        $this->loader = $loader;
        $this->pathProvider = $pathProvider;
    }

    /**
     * @return array<string,mixed>
     */
    private function getData(): array
    {
        if (!isset($this->data)) {
            $this->init();
        }

        assert($this->data !== null);

        return $this->data;
    }

    private function init(): void
    {
        $useCache = $this->config->get('useCache');

        if ($useCache && $this->dataCache->has($this->cacheKey)) {
            /** @var ?array<string,mixed> $data */
            $data = $this->dataCache->get($this->cacheKey);

            $this->data = $data;

            return;
        }

        $this->data = $this->loadData();

        if ($useCache) {
            $this->dataCache->store($this->cacheKey, $this->data);
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function loadData(): array
    {
        $corePath = $this->pathProvider->getCore() . $this->autoloadFileName;

        $data = $this->loadDataFromFile($corePath);

        foreach ($this->metadata->getModuleList() as $moduleName) {
            $modulePath = $this->pathProvider->getModule($moduleName) . $this->autoloadFileName;

            $data = array_merge_recursive(
                $data,
                $this->loadDataFromFile($modulePath)
            );
        }

        $customPath = $this->pathProvider->getCustom() . $this->autoloadFileName;

        return array_merge_recursive(
            $data,
            $this->loadDataFromFile($customPath)
        );
    }

    /**
     * @return array<string,mixed>
     * @throws \JsonException
     */
    private function loadDataFromFile(string $filePath): array
    {
        if (!$this->fileManager->isFile($filePath)) {
            return [];
        }

        $content = $this->fileManager->getContents($filePath);

        $arrayContent = Json::decode($content, true);

        return $this->normalizeData($arrayContent);
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function normalizeData(array $data): array
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

    public function register(): void
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
