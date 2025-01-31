<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils\Autoload;

use Espo\Core\Utils\Config\SystemConfig;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Util;

use Composer\Autoload\ClassLoader;

use Throwable;

class NamespaceLoader
{
    /**
     * @var ?array{
     *   psr-4?: array<string, mixed>,
     *   psr-0?: array<string, mixed>,
     *   classmap?: array<string, mixed>,
     * }
     */
    private $namespaces = null;
    /** @var ?array<string, mixed> */
    private $vendorNamespaces = null;
    private string $autoloadFilePath = 'vendor/autoload.php';
    /** @var array<'psr-4'|'psr-0'|'classmap', string> */
    private $namespacesPaths = [
        'psr-4' => 'vendor/composer/autoload_psr4.php',
        'psr-0' => 'vendor/composer/autoload_namespaces.php',
        'classmap' => 'vendor/composer/autoload_classmap.php',
    ];
    /** @var array<'psr-4'|'psr-0', string> */
    private $methodNameMap = [
        'psr-4' => 'addPsr4',
        'psr-0' => 'add',
    ];
    private string $cacheKey = 'autoloadVendorNamespaces';

    private ClassLoader $classLoader;

    public function __construct(
        private DataCache $dataCache,
        private FileManager $fileManager,
        private Log $log,
        private SystemConfig $systemConfig,
    ) {

        $this->classLoader = new ClassLoader();
    }

    /**
     * @param array{
     *   psr-4?: array<string, mixed>,
     *   psr-0?: array<string, mixed>
     * } $data
     */
    public function register(array $data): void
    {
        $this->addListToClassLoader($data);

        $this->classLoader->register(true);
    }

    /**
     * @return array{
     *   psr-4?: array<string, mixed>,
     *   psr-0?: array<string, mixed>,
     *   classmap?: array<string, mixed>,
     * }
     */
    private function loadNamespaces(string $basePath = ''): array
    {
        $namespaces = [];

        foreach ($this->namespacesPaths as $type => $path) {
            $mapFile = Util::concatPath($basePath, $path);

            if (!$this->fileManager->exists($mapFile)) {
                continue;
            }

            $map = require($mapFile);

            if (!empty($map) && is_array($map)) {
                $namespaces[$type] = $map;
            }
        }

        return $namespaces;
    }

    /**
     *
     * @return array{
     *   psr-4?: array<string, mixed>,
     *   psr-0?: array<string, mixed>,
     *   classmap?: array<string, mixed>,
     * }
     */
    private function getNamespaces(): array
    {
        if (!$this->namespaces) {
            $this->namespaces = $this->loadNamespaces();
        }

        return $this->namespaces;
    }

    /**
     * @param 'psr-4'|'psr-0'|'classmap' $type
     * @return string[]
     */
    private function getNamespaceList(string $type): array
    {
        $namespaces = $this->getNamespaces();

        return array_keys($namespaces[$type] ?? []);
    }

    /**
     * @param 'psr-4'|'psr-0'|'classmap' $type
     * @param string|array<string, string> $path
     */
    private function addNamespace(string $type, string $name, $path): void
    {
        if (!$this->namespaces) {
            $this->getNamespaces();
        }

        $this->namespaces[$type][$name] = (array) $path;
    }

    /**
     * @param 'psr-4'|'psr-0'|'classmap' $type
     */
    private function hasNamespace(string $type, string $name): bool
    {
        if (in_array($name, $this->getNamespaceList($type))) {
            return true;
        }

        if (!preg_match('/\\\$/', $name)) {
            $name = $name . '\\';

            if (in_array($name, $this->getNamespaceList($type))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function addListToClassLoader(array $data, bool $skipVendorNamespaces = false): void
    {
        foreach ($this->methodNameMap as $type => $methodName) {
            $itemData = $data[$type] ?? null;

            if ($itemData === null) {
                continue;
            }

            foreach ($itemData as $prefix => $path) {
                if (!$skipVendorNamespaces) {
                    $vendorPaths = is_array($path) ? $path : (array) $path;

                    foreach ($vendorPaths as $vendorPath) {
                        $this->addListToClassLoader(
                            $this->getVendorNamespaces($vendorPath),
                            true
                        );
                    }
                }

                if ($this->hasNamespace($type, $prefix)) {
                    continue;
                }

                try {
                    $this->classLoader->$methodName($prefix, $path);
                } catch (Throwable $e) {
                    $this->log->error("Could not add '{$prefix}' to autoload: " . $e->getMessage());

                    continue;
                }

                $this->addNamespace($type, $prefix, $path);
            }
        }

        $classMap = $data['classmap'] ?? null;

        if ($classMap !== null) {
            $this->classLoader->addClassMap($classMap);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getVendorNamespaces(string $path): array
    {
        $useCache = $this->systemConfig->useCache();

        if (!isset($this->vendorNamespaces)) {
            $this->vendorNamespaces = [];

            if ($useCache && $this->dataCache->has($this->cacheKey)) {
                /** @var ?array<string, mixed> $cachedData */
                $cachedData = $this->dataCache->get($this->cacheKey);

                $this->vendorNamespaces = $cachedData;
            }
        }

        assert($this->vendorNamespaces !== null);

        if (!array_key_exists($path, $this->vendorNamespaces)) {
            $vendorPath = $this->findVendorPath($path);

            if ($vendorPath) {
                $this->vendorNamespaces[$path] = $this->loadNamespaces($vendorPath);

                if ($useCache) {
                    $this->dataCache->store($this->cacheKey, $this->vendorNamespaces);
                }
            }
        }

        return $this->vendorNamespaces[$path] ?? [];
    }

    private function findVendorPath(string $path): ?string
    {
        $vendor = Util::concatPath($path, $this->autoloadFilePath);

        if ($this->fileManager->exists($vendor)) {
            return $path;
        }

        $parentDir = dirname($path);

        if (!empty($parentDir) && $parentDir !== '.') {
            return $this->findVendorPath($parentDir);
        }

        return null;
    }
}
