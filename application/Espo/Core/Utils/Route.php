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

use Espo\Core\Exceptions\Error;

class Route
{
    protected $data = null;

    protected $cacheFile = 'data/cache/application/routes.php';

    protected $paths = [
        'corePath' => 'application/Espo/Resources/routes.json',
        'modulePath' => 'application/Espo/Modules/{*}/Resources/routes.json',
        'customPath' => 'custom/Espo/Custom/Resources/routes.json',
    ];

    private $fileManager;
    private $config;
    private $metadata;

    public function __construct(Config $config, Metadata $metadata, File\Manager $fileManager)
    {
        $this->config = $config;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
    }

    /**
     * Get all routes.
     */
    public function getFullList() : array
    {
        if (!isset($this->data)) {
            $this->init();
        }
        return $this->data;
    }

    protected function init()
    {
        if (file_exists($this->cacheFile) && $this->config->get('useCache')) {
            $this->data = $this->fileManager->getPhpContents($this->cacheFile);
        } else {
            $this->data = $this->unify();

            if ($this->config->get('useCache')) {
                $result = $this->fileManager->putPhpContents($this->cacheFile, $this->data);
                if ($result == false) {
                    throw new Error('Route - Cannot save unified routes');
                }
            }
        }
    }

    /**
     * Unify routes.
     */
    protected function unify() : array
    {
        // for custom
        $data = $this->getAddData([], $this->paths['customPath']);

        // for module
        $moduleData = [];
        foreach ($this->metadata->getModuleList() as $moduleName) {
            $modulePath = str_replace('{*}', $moduleName, $this->paths['modulePath']);
            foreach ($this->getAddData([], $modulePath) as $row) {
                $moduleData[$row['method'].$row['route']] = $row;
            }
        }
        $data = array_merge($data, array_values($moduleData));

        // for core
        $data = $this->getAddData($data, $this->paths['corePath']);

        return $data;
    }

    protected function getAddData($currData, $routeFile)
    {
        if (file_exists($routeFile)) {
            $content = $this->fileManager->getContents($routeFile);
            $arrayContent = Json::getArrayData($content);
            if (empty($arrayContent)) {
                $GLOBALS['log']->error('Route::unify() - Empty file or syntax error - ['.$routeFile.']');
                return $currData;
            }

            $currData = $this->addToData($currData, $arrayContent);
        }

        return $currData;
    }

    protected function addToData($data, $newData)
    {
        foreach ($newData as $route) {
            $route['route'] = $this->adjustPath($route['route']);

            if (isset($route['conditions'])) {
                $route['noAuth'] = !($route['conditions']['auth'] ?? true);
                unset($route['conditions']);
            }
            $data[] = $route;
        }

        return $data;
    }

    /**
     * Check and adjust the route path.
     *
     * @param string $routePath - it can be "/App/user",  "App/user"
     *
     * @return string - "/App/user"
     */
    protected function adjustPath(string $routePath) : string
    {
        $routePath = trim($routePath);

        // to fast route format
        $routePath = preg_replace('/\:([a-zA-Z0-9]+)/', '{${1}}', $routePath);

        if (substr($routePath, 0, 1) != '/') {
            return '/'.$routePath;
        }

        return $routePath;
    }

    public static function detectBasePath() : string
    {
        $scriptName = parse_url($_SERVER['SCRIPT_NAME'] , PHP_URL_PATH);
        $scriptDir = dirname($scriptName);

        $uri = parse_url('http://any.com' . $_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $basePath = '';

        if (stripos($uri, $scriptName) === 0) {
            $basePath = $scriptName;
        } elseif ($scriptDir !== '/' && stripos($uri, $scriptDir) === 0) {
            $basePath = $scriptDir;
        }

        return $basePath;
    }
}
