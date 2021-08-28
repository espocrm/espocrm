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

use Espo\Core\Api\Route as RouteItem;

use Espo\Core\{
    Utils\Config,
    Utils\Metadata,
    Utils\File\Manager as FileManager,
    Utils\DataCache,
    Utils\Resource\PathProvider,
};

class Route
{
    private $data = null;

    private $cacheKey = 'routes';

    private $routesFileName = 'routes.json';

    private $config;

    private $metadata;

    private $fileManager;

    private $dataCache;

    private $pathProvider;

    public function __construct(
        Config $config,
        Metadata $metadata,
        FileManager $fileManager,
        DataCache $dataCache,
        PathProvider $pathProvider
    ) {
        $this->config = $config;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->dataCache = $dataCache;
        $this->pathProvider = $pathProvider;
    }

    /**
     * Get all routes.
     *
     * @return RouteItem[]
     */
    public function getFullList(): array
    {
        if (!isset($this->data)) {
            $this->init();
        }

        return array_map(
            function (array $item): RouteItem {
                return new RouteItem(
                    $item['method'],
                    $item['route'],
                    $item['params'] ?? [],
                    $item['noAuth'] ?? false
                );
            },
            $this->data
        );
    }

    private function init(): void
    {
        $useCache = $this->config->get('useCache');

        if ($this->dataCache->has($this->cacheKey) && $useCache) {
            $this->data = $this->dataCache->get($this->cacheKey);

            return;
        }

        $this->data = $this->unify();

        if ($useCache) {
            $this->dataCache->store($this->cacheKey, $this->data);
        }
    }

    private function unify(): array
    {
        $customData = $this->addDataFromFile([], $this->pathProvider->getCustom() . $this->routesFileName);

        $moduleData = [];

        foreach ($this->metadata->getModuleList() as $moduleName) {
            $moduleFilePath = $this->pathProvider->getModule($moduleName) . $this->routesFileName;

            foreach ($this->addDataFromFile([], $moduleFilePath) as $item) {
                $key = $item['method'] . $item['route'];

                $moduleData[$key] = $item;
            }
        }

        $data = array_merge($customData, array_values($moduleData));

        return $this->addDataFromFile(
            $data,
            $this->pathProvider->getCore() . $this->routesFileName
        );
    }

    private function addDataFromFile(array $currentData, string $routeFile): array
    {
        if (!$this->fileManager->exists($routeFile)) {
            return $currentData;
        }

        $content = $this->fileManager->getContents($routeFile);

        $data = Json::decode($content, true);

        return $this->appendRoutesToData($currentData, $data);
    }

    private function appendRoutesToData(array $data, array $newData): array
    {
        foreach ($newData as $route) {
            $route['route'] = $this->adjustPath($route['route']);

            if (isset($route['conditions'])) {
                $route['noAuth'] = !($route['conditions']['auth'] ?? true);

                unset($route['conditions']);
            }

            if (self::isRouteInList($route, $data)) {
                continue;
            }

            $data[] = $route;
        }

        return $data;
    }

    /**
     * Check and adjust the route path.
     */
    private function adjustPath(string $path): string
    {
        // to fast route format
        $pathFormatteted = preg_replace('/\:([a-zA-Z0-9]+)/', '{${1}}', trim($path));

        if (substr($pathFormatteted, 0, 1) !== '/') {
            return '/' . $pathFormatteted;
        }

        return $pathFormatteted;
    }

    public static function detectBasePath(): string
    {
        $scriptName = parse_url($_SERVER['SCRIPT_NAME'] , PHP_URL_PATH);

        $scriptNameModified = str_replace('public/api/', 'api/', $scriptName);

        $scriptDir = dirname($scriptNameModified);

        $uri = parse_url('http://any.com' . $_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (stripos($uri, $scriptName) === 0) {
            return $scriptName;
        }

        if ($scriptDir !== '/' && stripos($uri, $scriptDir) === 0) {
            return $scriptDir;
        }

        return '';
    }

    public static function detectEntryPointRoute(): string
    {
        $basePath = self::detectBasePath();

        $uri = parse_url('http://any.com' . $_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if ($uri === $basePath) {
            return '/';
        }

        if (stripos($uri, $basePath) === 0) {
            return substr($uri, strlen($basePath));
        }

        return '/';
    }

    static private function isRouteInList(array $newRoute, array $routeList): bool
    {
        foreach ($routeList as $route) {
            if (Util::areEqual($route, $newRoute)) {
                return true;
            }
        }

        return false;
    }
}
