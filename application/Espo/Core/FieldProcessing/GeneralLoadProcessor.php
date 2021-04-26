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
class GeneralLoadProcessor
{
    private $injectableFactory;

    private $metadata;

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

    public function process(Entity $entity): void
    {
        foreach ($this->createProcessorList($entity->getEntityType()) as $processor) {
            $processor->process($entity);
        }
    }

    /**
     * @return LoadProcessor[]
     */
    private function createProcessorList(string $entityType): array
    {
        $list = [];

        foreach ($this->getProcessorClassNameList($entityType) as $className) {
            $list[] = $this->createProcessor($className);
        }

        return $list;
    }

    /**
     * @return string[]
     */
    private function getProcessorClassNameList(string $entityType): array
    {
        $list = $this->metadata
            ->get(['app', 'fieldProcessing', 'loadProcessorClassNameList']) ?? [];

        $additionalList = $this->metadata
            ->get(['recordDefs', $entityType, 'loadProcessorClassNameList']) ?? [];

        return array_merge($list, $additionalList);
    }

    private function createProcessor(string $className): LoadProcessor
    {
        return $this->injectableFactory->create($className);
    }
}
