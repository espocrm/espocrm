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

namespace Espo\Core\Record;

use Espo\Core\{
    Exceptions\Error,
    ServiceFactory,
    Utils\Metadata,
};

/**
 * Container for record services. Lazy loading is used.
 * Usually there's no need to have multiple record service instances of the same entity type.
 * Use this container instead of serviceFactory to get record services.
 */
class ServiceContainer
{
    private const RECORD_SERVICE_NAME = 'Record';

    private const RECORD_TREE_SERVICE_NAME = 'RecordTree';

    private $defaultTypeMap = [
        'CategoryTree' => self::RECORD_TREE_SERVICE_NAME,
    ];

    private $data = [];

    private $serviceFactory;

    private $metadata;

    public function __construct(ServiceFactory $serviceFactory, Metadata $metadata)
    {
        $this->serviceFactory = $serviceFactory;
        $this->metadata = $metadata;
    }

    public function get(string $entityType): Service
    {
        if (!array_key_exists($entityType, $this->data)) {
            $this->load($entityType);
        }

        return $this->data[$entityType];
    }

    private function load(string $entityType): void
    {
        if (!$this->metadata->get(['scopes', $entityType, 'entity'])) {
            throw new Error("Can't create record service '{$entityType}', there's no such entity type.");
        }

        if ($this->serviceFactory->checkExists($entityType)) {
            $service = $this->serviceFactory->create($entityType);

            if (!$service instanceof Service) {
                $this->loadDefault($entityType);

                return;
            }

            $this->data[$entityType] = $service;

            return;
        }

        $this->loadDefault($entityType);
    }

    private function loadDefault(string $entityType): void
    {
        $default = self::RECORD_SERVICE_NAME;

        $type = $this->metadata->get(['scopes', $entityType, 'type']);

        if ($type) {
            $default = $this->defaultTypeMap[$type] ?? $default;
        }

        $obj = $this->serviceFactory->create($default);

        $obj->setEntityType($entityType);

        $this->data[$entityType] = $obj;
    }
}
