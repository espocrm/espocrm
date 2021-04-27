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

namespace Espo\Core\FieldProcessing;

use Espo\Core\ORM\Entity;

use Espo\Core\{
    InjectableFactory,
    Utils\Metadata,
};

/**
 * Processes loading special fields (before output).
 */
class ReadLoadProcessor
{
    private $injectableFactory;

    private $metadata;

    private $processorListMapCache = [];

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

    public function process(Entity $entity, ?LoadProcessorParams $params = null): void
    {
        if (!$params) {
            $params = new LoadProcessorParams();
        }

        foreach ($this->getProcessorList($entity->getEntityType()) as $processor) {
            $processor->process($entity, $params);
        }
    }

    /**
     * @return LoadProcessor[]
     */
    private function getProcessorList(string $entityType): array
    {
        if (array_key_exists($entityType, $this->processorListMapCache)) {
            return $this->processorListMapCache[$entityType];
        }

        $list = [];

        foreach ($this->getProcessorClassNameList($entityType) as $className) {
            $list[] = $this->createProcessor($className);
        }

        $this->processorListMapCache[$entityType] = $list;

        return $list;
    }

    /**
     * @return string[]
     */
    private function getProcessorClassNameList(string $entityType): array
    {
        $list = $this->metadata
            ->get(['app', 'fieldProcessing', 'readLoadProcessorClassNameList']) ?? [];

        $additionalList = $this->metadata
            ->get(['recordDefs', $entityType, 'readLoadProcessorClassNameList']) ?? [];

        return array_merge($list, $additionalList);
    }

    private function createProcessor(string $className): LoadProcessor
    {
        return $this->injectableFactory->create($className);
    }
}
