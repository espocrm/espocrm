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

namespace Espo\Core\ORM;

use Espo\Core\Utils\Config;
use Espo\Core\InjectableFactory;
use Espo\Core\Binding\BindingContainerBuilder;

use Espo\ORM\Metadata;
use Espo\ORM\EventDispatcher;
use Espo\ORM\DatabaseParams;
use Espo\ORM\Repository\RepositoryFactory as RepositoryFactoryInterface;
use Espo\ORM\EntityFactory as EntityFactoryInteface;
use Espo\ORM\Value\ValueFactoryFactory as ValueFactoryFactoryInteface;
use Espo\ORM\Value\AttributeExtractorFactory as AttributeExtractorFactoryInteface;
use Espo\ORM\PDO\PDOProvider;
use Espo\ORM\PDO\DefaultPDOProvider;
use Espo\ORM\QueryComposer\Part\FunctionConverterFactory as FunctionConverterFactoryInterface;

use Espo\Core\ORM\QueryComposer\Part\FunctionConverterFactory;

use RuntimeException;

class EntityManagerFactory
{
    private $config;

    private $injectableFactory;

    private $metadataDataProvider;

    private $eventDispatcher;

    private $driverPlatformMap = [
        'pdo_mysql' => 'Mysql',
        'mysqli' => 'Mysql',
    ];

    public function __construct(
        Config $config,
        InjectableFactory $injectableFactory,
        MetadataDataProvider $metadataDataProvider,
        EventDispatcher $eventDispatcher
    ) {
        $this->config = $config;
        $this->injectableFactory = $injectableFactory;
        $this->metadataDataProvider = $metadataDataProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function create(): EntityManager
    {
        $entityFactory = $this->injectableFactory->create(EntityFactory::class);

        $repositoryFactory = $this->injectableFactory->createWithBinding(
            RepositoryFactory::class,
            BindingContainerBuilder::create()
                ->bindInstance(EntityFactoryInteface::class, $entityFactory)
                ->build()
        );

        $databaseParams = $this->createDatabaseParams();

        $metadata = new Metadata($this->metadataDataProvider, $this->eventDispatcher);

        $valueFactoryFactory = $this->injectableFactory->createWithBinding(
            ValueFactoryFactory::class,
            BindingContainerBuilder::create()
                ->bindInstance(Metadata::class, $metadata)
                ->build()
        );

        $attributeExtractorFactory = $this->injectableFactory->createWithBinding(
            AttributeExtractorFactory::class,
            BindingContainerBuilder::create()
                ->bindInstance(Metadata::class, $metadata)
                ->build()
        );

        $functionConverterFactory = $this->injectableFactory->createWithBinding(
            FunctionConverterFactory::class,
            BindingContainerBuilder::create()
                ->bindInstance(DatabaseParams::class, $databaseParams)
                ->build()
        );

        $binding = BindingContainerBuilder::create()
            ->bindInstance(DatabaseParams::class, $databaseParams)
            ->bindInstance(Metadata::class, $metadata)
            ->bindInstance(RepositoryFactoryInterface::class, $repositoryFactory)
            ->bindInstance(EntityFactoryInteface::class, $entityFactory)
            ->bindInstance(ValueFactoryFactoryInteface::class, $valueFactoryFactory)
            ->bindInstance(AttributeExtractorFactoryInteface::class, $attributeExtractorFactory)
            ->bindInstance(EventDispatcher::class, $this->eventDispatcher)
            ->bindImplementation(PDOProvider::class, DefaultPDOProvider::class)
            ->bindInstance(FunctionConverterFactoryInterface::class, $functionConverterFactory)
            ->build();

        return $this->injectableFactory->createWithBinding(EntityManager::class, $binding);
    }

    private function createDatabaseParams(): DatabaseParams
    {
        $config = $this->config;

        $databaseParams = DatabaseParams::create()
            ->withHost($config->get('database.host'))
            ->withPort($config->get('database.port') ? (int) $config->get('database.port') : null)
            ->withName($config->get('database.dbname'))
            ->withUsername($config->get('database.user'))
            ->withPassword($config->get('database.password'))
            ->withCharset($config->get('database.charset') ?? 'utf8')
            ->withPlatform($config->get('database.platform'))
            ->withSslCa($config->get('database.sslCA'))
            ->withSslCert($config->get('database.sslCert'))
            ->withSslKey($config->get('database.sslKey'))
            ->withSslCaPath($config->get('database.sslCAPath'))
            ->withSslCipher($config->get('database.sslCipher'))
            ->withSslVerifyDisabled($config->get('database.sslVerifyDisabled') ?? false);

        if (!$databaseParams->getName()) {
            throw new RuntimeException('No database name specified.');
        }

        if (!$databaseParams->getPlatform()) {
            $driver = $config->get('database.driver');

            if (!$driver) {
                throw new RuntimeException('No database driver specified.');
            }

            $platform = $this->driverPlatformMap[$driver] ?? null;

            if (!$platform) {
                throw new RuntimeException("Database driver '{$driver}' is not supported.");
            }

            $databaseParams = $databaseParams->withPlatform($platform);
        }

        return $databaseParams;
    }
}
