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

namespace Espo\Core;

use Espo\Core\{
    InjectableFactory,
    Utils\File\Manager as FileManager,
    Utils\Metadata,
    Utils\Config,
    Utils\Util,
    Utils\DataCache,
    Utils\Log,
    Utils\Module\PathProvider,
};

/**
 * Runs hooks. E.g. beforeSave, afterSave. Hooks can be located in a folder
 * that matches a certain *entityType* or in the `Common` folder.
 * Common hooks are applied to all entity types.
 */
class HookManager
{
    private const DEFAULT_ORDER = 9;

    private $data;

    private $isDisabled;

    private $hookListHash = [];

    private $hooks;

    private $cacheKey = 'hooks';

    private $ignoredMethodList = [
        '__construct',
        'getDependencyList',
        'inject',
    ];

    private $injectableFactory;

    private $fileManager;

    private $metadata;

    private $config;

    private $dataCache;

    private $log;

    private $pathProvider;

    public function __construct(
        InjectableFactory $injectableFactory,
        FileManager $fileManager,
        Metadata $metadata,
        Config $config,
        DataCache $dataCache,
        Log $log,
        PathProvider $pathProvider
    ) {
        $this->injectableFactory = $injectableFactory;
        $this->fileManager = $fileManager;
        $this->metadata = $metadata;
        $this->config = $config;
        $this->dataCache = $dataCache;
        $this->log = $log;
        $this->pathProvider = $pathProvider;
    }

    public function process(
        string $scope,
        string $hookName,
        $injection = null,
        array $options = [],
        array $hookData = []
    ): void {

        if ($this->isDisabled) {
            return;
        }

        if (!isset($this->data)) {
            $this->loadHooks();
        }

        $hookList = $this->getHookList($scope, $hookName);

        if (empty($hookList)) {
            return;
        }

        foreach ($hookList as $className) {
            if (empty($this->hooks[$className])) {
                $this->hooks[$className] = $this->createHookByClassName($className);
            }

            $hook = $this->hooks[$className];

            $hook->$hookName($injection, $options, $hookData);
        }
    }

    /**
     * Disable hook processing.
     */
    public function disable(): void
    {
        $this->isDisabled = true;
    }

    /**
     * Enable hook processing.
     */
    public function enable(): void
    {
        $this->isDisabled = false;
    }

    private function loadHooks(): void
    {
        if ($this->config->get('useCache') && $this->dataCache->has($this->cacheKey)) {
            $this->data = $this->dataCache->get($this->cacheKey);

            return;
        }

        $metadata = $this->metadata;

        $data = $this->readHookData($this->pathProvider->getCustom() . 'Hooks');

        foreach ($metadata->getModuleList() as $moduleName) {
            $modulePath = $this->pathProvider->getModule($moduleName) . 'Hooks';

            $data = $this->readHookData($modulePath, $data);
        }

        $data = $this->readHookData($this->pathProvider->getCore() . 'Hooks', $data);

        $this->data = $this->sortHooks($data);

        if ($this->config->get('useCache')) {
            $this->dataCache->store($this->cacheKey, $this->data);
        }
    }

    private function createHookByClassName(string $className): object
    {
        if (!class_exists($className)) {
            $this->log->error("Hook class '{$className}' does not exist.");
        }

        $obj = $this->injectableFactory->create($className);

        return $obj;
    }

    private function readHookData(string $hookDir, array $hookData = []): array
    {
        if (!$this->fileManager->exists($hookDir)) {
            return $hookData;
        }

        $fileList = $this->fileManager->getFileList($hookDir, 1, '\.php$', true);

        foreach ($fileList as $scopeName => $hookFiles) {
            $hookScopeDirPath = Util::concatPath($hookDir, $scopeName);
            $normalizedScopeName = Util::normilizeScopeName($scopeName);

            foreach ($hookFiles as $hookFile) {
                $hookFilePath = Util::concatPath($hookScopeDirPath, $hookFile);
                $className = Util::getClassName($hookFilePath);

                $classMethods = get_class_methods($className);

                $hookMethods = array_diff($classMethods, $this->ignoredMethodList);

                $hookMethods = array_filter($hookMethods, function ($item) {
                    if (strpos($item, 'set') === 0) {
                        return false;
                    }

                    return true;
                });

                foreach ($hookMethods as $hookType) {
                    $entityHookData = $hookData[$normalizedScopeName][$hookType] ?? [];

                    if ($this->hookExists($className, $entityHookData)) {
                        continue;
                    }

                    $hookData[$normalizedScopeName][$hookType][] = [
                        'className' => $className,
                        'order' => $className::$order ?? self::DEFAULT_ORDER,
                    ];
                }
            }
        }

        return $hookData;
    }

    /**
     * Sort hooks by the order parameter.
     */
    private function sortHooks(array $hooks): array
    {
        foreach ($hooks as &$scopeHooks) {
            foreach ($scopeHooks as &$hookList) {
                usort($hookList, [$this, 'cmpHooks']);
            }
        }

        return $hooks;
    }

    /**
     * Get sorted hook list.
     */
    private function getHookList(string $scope, string $hookName): array
    {
        $key = $scope . '_' . $hookName;

        if (!isset($this->hookListHash[$key])) {
            $hookList = [];

            if (isset($this->data['Common'][$hookName])) {
                $hookList = $this->data['Common'][$hookName];
            }

            if (isset($this->data[$scope][$hookName])) {
                $hookList = array_merge($hookList, $this->data[$scope][$hookName]);

                usort($hookList, array($this, 'cmpHooks'));
            }

            $normalizedList = [];

            foreach ($hookList as $hookData) {
                $normalizedList[] = $hookData['className'];
            }

            $this->hookListHash[$key] = $normalizedList;
        }

        return $this->hookListHash[$key];
    }

    /**
     * Check if hook exists in the list.
     */
    private function hookExists(string $className, array $hookData): bool
    {
        $class = preg_replace('/^.*\\\(.*)$/', '$1', $className);

        foreach ($hookData as $hookData) {
            if (preg_match('/\\\\'.$class.'$/', $hookData['className'])) {
                return true;
            }
        }

        return false;
    }

    private function cmpHooks($a, $b): int
    {
        if ($a['order'] == $b['order']) {
            return 0;
        }

        return ($a['order'] < $b['order']) ? -1 : 1;
    }
}
