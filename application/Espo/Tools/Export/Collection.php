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

namespace Espo\Tools\Export;

use Espo\Core\FieldProcessing\ListLoadProcessor;
use Espo\Core\FieldProcessing\Loader\Params as LoaderParams;
use Espo\Core\Record\Service as RecordService;
use Espo\ORM\Collection as OrmCollection;
use Espo\ORM\Entity;
use Espo\Tools\Export\Processor\Params as ProcessorParams;
use IteratorAggregate;
use Traversable;

/**
 * A lazy-iterable collection of entities.
 *
 * @implements IteratorAggregate<int, Entity>
 */
class Collection implements IteratorAggregate
{
    /**
     * @param OrmCollection<Entity> $collection
     * @param RecordService<Entity> $recordService
     */
    public function __construct(
        private OrmCollection $collection,
        private ListLoadProcessor $listLoadProcessor,
        private LoaderParams $loaderParams,
        private ?AdditionalFieldsLoader $additionalFieldsLoader,
        private RecordService $recordService,
        private ProcessorParams $processorParams
    ) {}

    public function getIterator(): Traversable
    {
        return (function () {
            foreach ($this->collection as $entity) {
                $this->prepareEntity($entity);

                yield $entity;
            }
        })();
    }

    private function prepareEntity(Entity $entity): void
    {
        $this->listLoadProcessor->process($entity, $this->loaderParams);

        /** For bc. */
        if (method_exists($this->recordService, 'loadAdditionalFieldsForExport')) {
            $this->recordService->loadAdditionalFieldsForExport($entity);
        }

        if ($this->additionalFieldsLoader && $this->processorParams->getFieldList()) {
            $this->additionalFieldsLoader->load($entity, $this->processorParams->getFieldList());
        }

        foreach ($entity->getAttributeList() as $attribute) {
            $this->prepareEntityValue($entity, $attribute);
        }
    }

    private function prepareEntityValue(Entity $entity, string $attribute): void
    {
        if (!in_array($attribute, $this->processorParams->getAttributeList())) {
            $entity->clear($attribute);
        }
    }
}
