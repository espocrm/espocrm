<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

namespace Espo\Core;

use \Espo\Core\Exceptions\Error,
    \Espo\Core\Utils\Util;

class HookManager
{
    private $container;

    private $data;

    private $hooks;

    protected $cacheFile = 'data/cache/application/hooks.php';

    /**
     * List of defined hooks
     *
     * @var array
     */
    protected $hookList = array(
        'beforeSave',
        'afterSave',
        'beforeRemove',
        'afterRemove',
    );

    protected $paths = array(
        'corePath' => 'application/Espo/Hooks',
        'modulePath' => 'application/Espo/Modules/{*}/Hooks',
        'customPath' => 'custom/Espo/Custom/Hooks',
    );


    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->loadHooks();
    }

    protected function getConfig()
    {
        return $this->container->get('config');
    }

    protected function getFileManager()
    {
        return $this->container->get('fileManager');
    }

    protected function loadHooks()
    {
        if ($this->getConfig()->get('useCache') && file_exists($this->cacheFile)) {
            $this->data = $this->getFileManager()->getContents($this->cacheFile);
            return;
        }

        $metadata = $this->container->get('metadata');

        $this->data = $this->getHookData($this->paths['corePath']);

        foreach ($metadata->getModuleList() as $moduleName) {
            $modulePath = str_replace('{*}', $moduleName, $this->paths['modulePath']);
            $this->data = array_merge($this->data, $this->getHookData($modulePath));
        }

        $this->data = array_merge($this->data, $this->getHookData($this->paths['customPath']));

        if ($this->getConfig()->get('useCache')) {
            $this->getFileManager()->putContentsPHP($this->cacheFile, $this->data);
        }
    }

    public function process($scope, $hookName, $injection = null)
    {
        if ($scope != 'Common') {
            $this->process('Common', $hookName, $injection);
        }

        if (!empty($this->data[$scope])) {
            if (!empty($this->data[$scope][$hookName])) {
                foreach ($this->data[$scope][$hookName] as $className) {
                    if (empty($this->hooks[$className])) {
                        $this->hooks[$className] = $this->createHookByClassName($className);
                    }
                    $hook = $this->hooks[$className];
                    $hook->$hookName($injection);
                }
            }
        }
    }

    public function createHookByClassName($className)
    {
        if (class_exists($className)) {
            $hook = new $className();
            $dependencies = $hook->getDependencyList();
            foreach ($dependencies as $name) {
                $hook->inject($name, $this->container->get($name));
            }
            return $hook;
        }
        throw new Error("Class '$className' does not exist");
    }

    /**
     * Get and merge hook data by checking the files exist in $hookDirs
     *
     * @param array $hookDirs - it can be an array('Espo/Hooks', 'Espo/Custom/Hooks', 'Espo/Modules/Crm/Hooks')
     *
     * @return array
     */
    protected function getHookData($hookDirs)
    {
        if (is_string($hookDirs)) {
            $hookDirs = (array) $hookDirs;
        }

        $hooks = array();

        foreach ($hookDirs as $hookDir) {

            if (file_exists($hookDir)) {
                $fileList = $this->getFileManager()->getFileList($hookDir, 1, '\.php$', true);

                foreach ($fileList as $scopeName => $hookFiles) {

                    $hookScopeDirPath = Util::concatPath($hookDir, $scopeName);

                    $scopeHooks = array();
                    foreach($hookFiles as $hookFile) {
                        $hookFilePath = Util::concatPath($hookScopeDirPath, $hookFile);
                        $className = Util::getClassName($hookFilePath);

                        foreach($this->hookList as $hookName) {
                            if (method_exists($className, $hookName)) {
                                $scopeHooks[$hookName][$className::$order][] = $className;
                            }
                        }
                    }

                    //sort hooks by order
                    foreach ($scopeHooks as $hookName => $hookList) {
                        ksort($hookList);

                        $sortedHookList = array();
                        foreach($hookList as $hookDetails) {
                            $sortedHookList = array_merge($sortedHookList, $hookDetails);
                        }

                        $hooks[$scopeName][$hookName] = isset($hooks[$scopeName][$hookName]) ? array_merge($hooks[$scopeName][$hookName], $sortedHookList) : $sortedHookList;
                    }
                }
            }

        }

        return $hooks;
    }

}

