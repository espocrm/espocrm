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

use Espo\Core\FieldProcessing\Saver\Params;

use Espo\Core\{
    InjectableFactory,
    Utils\Metadata,
};

/**
 * Processes saving special fields.
 */
class SaveProcessor
{
    private $injectableFactory;

    private $metadata;

    private $saverListMapCache = [];

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

    public function process(Entity $entity, array $options): void
    {
        $params = Params::create()->withRawOptions($options);

        foreach ($this->getSaverList($entity->getEntityType()) as $processor) {
            $processor->process($entity, $params);
        }
    }

    /**
     * @return Saver[]
     */
    private function getSaverList(string $entityType): array
    {
        if (array_key_exists($entityType, $this->saverListMapCache)) {
            return $this->saverListMapCache[$entityType];
        }

        $list = [];

        foreach ($this->getSaverClassNameList($entityType) as $className) {
            $list[] = $this->createSaver($className);
        }

        $this->saverListMapCache[$entityType] = $list;

        return $list;
    }

    /**
     * @return string[]
     */
    private function getSaverClassNameList(string $entityType): array
    {
        $list = $this->metadata
            ->get(['app', 'fieldProcessing', 'saverClassNameList']) ?? [];

        $additionalList = $this->metadata
            ->get(['recordDefs', $entityType, 'saverClassNameList']) ?? [];

        return array_merge($list, $additionalList);
    }

    private function createSaver(string $className): Saver
    {
        return $this->injectableFactory->create($className);
    }
}
