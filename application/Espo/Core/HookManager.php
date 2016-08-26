<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
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
            $this->data = $this->getFileManager()->getPhpContents($this->cacheFile);
            return;
        }

        $metadata = $this->container->get('metadata');

        $data = $this->getHookData($this->paths['customPath']);

        foreach ($metadata->getModuleList() as $moduleName) {
            $modulePath = str_replace('{*}', $moduleName, $this->paths['modulePath']);
            $data = $this->getHookData($modulePath, $data);
        }

        $data = $this->getHookData($this->paths['corePath'], $data);

        $this->data = $this->sortHooks($data);

        if ($this->getConfig()->get('useCache')) {
            $this->getFileManager()->putPhpContents($this->cacheFile, $this->data);
        }
    }

    public function process($scope, $hookName, $injection = null, array $options = array())
    {
        if (!isset($this->data)) {
            $this->loadHooks();
        }

        if ($scope != 'Common') {
            $this->process('Common', $hookName, $injection, $options);
        }

        if (!empty($this->data[$scope])) {
            if (!empty($this->data[$scope][$hookName])) {
                foreach ($this->data[$scope][$hookName] as $className) {
                    if (empty($this->hooks[$className])) {
                        $this->hooks[$className] = $this->createHookByClassName($className);
                        if (empty($this->hooks[$className])) {
                            continue;
                        }
                    }
                    $hook = $this->hooks[$className];
                    $hook->$hookName($injection, $options);
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

        $GLOBALS['log']->error("Hook class '{$className}' does not exist.");
    }

    /**
     * Get and merge hook data by checking the files exist in $hookDirs
     *
     * @param array $hookDirs - it can be an array('Espo/Hooks', 'Espo/Custom/Hooks', 'Espo/Modules/Crm/Hooks')
     *
     * @return array
     */
    protected function getHookData($hookDirs, array $hookData = array())
    {
        if (is_string($hookDirs)) {
            $hookDirs = (array) $hookDirs;
        }

        foreach ($hookDirs as $hookDir) {

            if (file_exists($hookDir)) {
                $fileList = $this->getFileManager()->getFileList($hookDir, 1, '\.php$', true);

                foreach ($fileList as $scopeName => $hookFiles) {

                    $hookScopeDirPath = Util::concatPath($hookDir, $scopeName);
                    $normalizedScopeName = Util::normilizeScopeName($scopeName);

                    $scopeHooks = array();
                    foreach($hookFiles as $hookFile) {
                        $hookFilePath = Util::concatPath($hookScopeDirPath, $hookFile);
                        $className = Util::getClassName($hookFilePath);

                        foreach($this->hookList as $hookName) {
                            $entityHookData = isset($hookData[$scopeName][$hookName]) ? $hookData[$scopeName][$hookName] : array();
                            if (method_exists($className, $hookName) && !$this->isHookExists($className, $entityHookData)) {
                                $hookData[$normalizedScopeName][$hookName][$className::$order][] = $className;
                            }
                        }
                    }
                }
            }

        }

        return $hookData;
    }

    /**
     * Sort hooks by an order
     *
     * @param  array  $scopeHooks
     *
     * @return array
     */
    protected function sortHooks(array $unsortedHooks)
    {
        $hooks = array();

        foreach ($unsortedHooks as $scopeName => $scopeHooks) {
            foreach ($scopeHooks as $hookName => $hookList) {
                ksort($hookList);

                $sortedHookList = array();
                foreach($hookList as $hookDetails) {
                    $sortedHookList = array_merge($sortedHookList, $hookDetails);
                }

                $normalizedScopeName = Util::normilizeScopeName($scopeName);

                $hooks[$normalizedScopeName][$hookName] = isset($hooks[$normalizedScopeName][$hookName]) ? array_merge($hooks[$normalizedScopeName][$hookName], $sortedHookList) : $sortedHookList;
            }
        }

        return $hooks;
    }

    /**
     * Check if hook exists in the list
     *
     * @param  string  $className
     * @param  array  $hookData
     *
     * @return boolean
     */
    protected function isHookExists($className, array $hookData)
    {
        $class = preg_replace('/^.*\\\(.*)$/', '$1', $className);

        foreach ($hookData as $key => $hookList) {
            foreach ($hookList as $rowHookName) {
                if (preg_match('/\\'.$class.'$/', $rowHookName)) {
                    return true;
                }
            }
        }

        return false;
    }
}

