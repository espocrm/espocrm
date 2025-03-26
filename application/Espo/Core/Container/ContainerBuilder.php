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

namespace Espo\Core\Container;

use Espo\Core\Container;
use Espo\Core\Container\Container as ContainerInterface;

use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingLoader;
use Espo\Core\Binding\EspoBindingLoader;

use Espo\Core\Loaders\ApplicationState as ApplicationStateLoader;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Config\ConfigFileManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\Module;

use Espo\Core\Loaders\Log as LogLoader;
use Espo\Core\Loaders\DataManager as DataManagerLoader;
use Espo\Core\Loaders\Metadata as MetadataLoader;

/**
 * Builds a service container.
 */
class ContainerBuilder
{
    /** @var class-string<ContainerInterface> */
    private string $containerClassName = Container::class;
    /** @var class-string<Configuration> */
    private string $containerConfigurationClassName = ContainerConfiguration::class;
    /** @var class-string */
    private string $configClassName = Config::class;
    /** @var class-string */
    private string $fileManagerClassName = FileManager::class;
    /** @var class-string */
    private string $dataCacheClassName = DataCache::class;
    /** @var class-string<Module> */
    private string $moduleClassName = Module::class;
    private ?BindingLoader $bindingLoader = null;
    /** @var array<string, object> */
    private $services = [];
    /** @var array<string, class-string<Loader>> */
    protected $loaderClassNames = [
        'log' => LogLoader::class,
        'dataManager' => DataManagerLoader::class,
        'metadata' => MetadataLoader::class,
        'applicationState' => ApplicationStateLoader::class,
    ];

    public function withBindingLoader(BindingLoader $bindingLoader): self
    {
        $this->bindingLoader = $bindingLoader;

        return $this;
    }

    /**
     * @param array<string, object> $services
     */
    public function withServices(array $services): self
    {
        foreach ($services as $key => $value) {
            $this->services[$key] = $value;
        }

        return $this;
    }

    /**
     * @param array<string, class-string<Loader>> $classNames
     * @noinspection PhpUnused
     */
    public function withLoaderClassNames(array $classNames): self
    {
        foreach ($classNames as $key => $value) {
            $this->loaderClassNames[$key] = $value;
        }

        return $this;
    }

    /**
     * @param class-string<ContainerInterface> $containerClassName
     */
    public function withContainerClassName(string $containerClassName): self
    {
        $this->containerClassName = $containerClassName;

        return $this;
    }

    /**
     * @param class-string<Configuration> $containerConfigurationClassName
     */
    public function withContainerConfigurationClassName(string $containerConfigurationClassName): self
    {
        $this->containerConfigurationClassName = $containerConfigurationClassName;

        return $this;
    }

    /**
     * @param class-string $configClassName
     */
    public function withConfigClassName(string $configClassName): self
    {
        $this->configClassName = $configClassName;

        return $this;
    }

    /**
     * @param class-string $fileManagerClassName
     * @noinspection PhpUnused
     */
    public function withFileManagerClassName(string $fileManagerClassName): self
    {
        $this->fileManagerClassName = $fileManagerClassName;

        return $this;
    }

    /**
     * @param class-string $dataCacheClassName
     * @noinspection PhpUnused
     */
    public function withDataCacheClassName(string $dataCacheClassName): self
    {
        $this->dataCacheClassName = $dataCacheClassName;

        return $this;
    }

    public function build(): ContainerInterface
    {
        /** @var Config $config */
        $config = $this->services['config'] ?? (
            new $this->configClassName(
                new ConfigFileManager()
            )
        );

        /** @var FileManager $fileManager */
        $fileManager = $this->services['fileManager'] ?? (
            new $this->fileManagerClassName(
                $config->get('defaultPermissions')
            )
        );

        /** @var DataCache $dataCache */
        $dataCache = $this->services['dataCache'] ?? (
            new $this->dataCacheClassName($fileManager)
        );

        $useCache = $config->get('useCache') ?? false;

        /** @var Module $module */
        $module = $this->services['module'] ?? (
            new $this->moduleClassName($fileManager, $dataCache, $useCache)
        );

        $systemConfig = new Config\SystemConfig($config);

        $this->services['config'] = $config;
        $this->services['fileManager'] = $fileManager;
        $this->services['dataCache'] = $dataCache;
        $this->services['module'] = $module;
        $this->services['systemConfig'] = $systemConfig;

        $bindingLoader = $this->bindingLoader ?? (
            new EspoBindingLoader($module)
        );

        $bindingContainer = new BindingContainer($bindingLoader->load());

        return new $this->containerClassName(
            $this->containerConfigurationClassName,
            $bindingContainer,
            $this->loaderClassNames,
            $this->services
        );
    }
}
