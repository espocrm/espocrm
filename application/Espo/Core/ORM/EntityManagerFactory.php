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

use Espo\Core\{
    Utils\Config,
    InjectableFactory,
};

use Espo\{
    ORM\Metadata,
    ORM\EventDispatcher,
};

class EntityManagerFactory
{
    private $config;

    private $injectableFactory;

    private $metadataDataProvider;

    private $eventDispatcher;

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

    public function create() : EntityManager
    {
        $entityFactory = $this->injectableFactory->create(EntityFactory::class);

        $repositoryFactory = $this->injectableFactory->createWith(RepositoryFactory::class, [
            'entityFactory' => $entityFactory,
        ]);

        $helper = $this->injectableFactory->create(Helper::class);

        $config = $this->config;

        $params = [
            'host' => $config->get('database.host'),
            'port' => $config->get('database.port'),
            'dbname' => $config->get('database.dbname'),
            'user' => $config->get('database.user'),
            'charset' => $config->get('database.charset', 'utf8'),
            'password' => $config->get('database.password'),
            'driver' => $config->get('database.driver'),
            'platform' => $config->get('database.platform'),
            'sslCA' => $config->get('database.sslCA'),
            'sslCert' => $config->get('database.sslCert'),
            'sslKey' => $config->get('database.sslKey'),
            'sslCAPath' => $config->get('database.sslCAPath'),
            'sslCipher' => $config->get('database.sslCipher'),
        ];

        $metadata = new Metadata($this->metadataDataProvider, $this->eventDispatcher);

        $valueFactoryFactory = $this->injectableFactory->createWith(
            ValueFactoryFactory::class,
            [
                'ormMetadata' => $metadata,
            ]
        );

        $attributeExtractorFactory = $this->injectableFactory->createWith(
            AttributeExtractorFactory::class,
            [
                'ormMetadata' => $metadata,
            ]
        );

        $entityManager = $this->injectableFactory->createWith(
            EntityManager::class,
            [
                'params' => $params,
                'metadata' => $metadata,
                'repositoryFactory' => $repositoryFactory,
                'entityFactory' => $entityFactory,
                'valueFactoryFactory' => $valueFactoryFactory,
                'attributeExtractorFactory' => $attributeExtractorFactory,
                'eventDispatcher' => $this->eventDispatcher,
                'helper' => $helper,
            ]
        );

        return $entityManager;
    }
}
