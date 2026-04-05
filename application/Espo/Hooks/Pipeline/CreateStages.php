<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Hooks\Pipeline;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Language;
use Espo\Entities\Pipeline;
use Espo\Entities\PipelineStage;
use Espo\ORM\Defs;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Tools\OpenApi\Util\EnumOptionsProvider;

/**
 * @implements AfterSave<Pipeline>
 */
class CreateStages implements AfterSave
{
    public function __construct(
        private EntityManager $entityManager,
        private Language $defaultLanguage,
        private Defs $defs,
        private EnumOptionsProvider $enumOptionsProvider,
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        $targetEntityType = $entity->getTargetEntityType();
        $field = $entity->getTargetField();

        if (
            !$entity->isNew() ||
            !$options->get(SaveOption::API) && !$options->get('createStages')
        ) {
            return;
        }

        if ($options->get(SaveOption::DUPLICATE_SOURCE_ID)) {
            $this->processDuplicate($entity, $options->get(SaveOption::DUPLICATE_SOURCE_ID));

            return;
        }

        $fieldDefs = $this->defs
            ->getEntity($targetEntityType)
            ->getField($field);

        $options = $this->enumOptionsProvider->get($fieldDefs) ?? [];

        foreach ($options as $i => $option) {
            $column = $this->entityManager->getRDBRepositoryByClass(PipelineStage::class)->getNew();

            $name = $this->defaultLanguage->translateOption($option, $field, $targetEntityType);

            $column
                ->setName($name)
                ->setMappedStatus($option)
                ->setPipeline($entity)
                ->setOrder($i);

            $this->entityManager->saveEntity($column);
        }
    }

    private function processDuplicate(Pipeline $entity, string $sourceId): void
    {
        $source = $this->entityManager->getRDBRepositoryByClass(Pipeline::class)->getById($sourceId);

        if (!$source) {
            return;
        }

        /** @var iterable<PipelineStage> $sourceStages */
        $sourceStages = $this->entityManager
            ->getRelation($source, Pipeline::LINK_STAGES)
            ->order(PipelineStage::FIELD_ORDER)
            ->find();

        foreach ($sourceStages as $i => $sourceStage) {
            $stage = $this->entityManager->getRDBRepositoryByClass(PipelineStage::class)->getNew();

            $stage
                ->setName($sourceStage->getName())
                ->setMappedStatus($sourceStage->getMappedStatus())
                ->setOrder($i)
                ->setPipeline($entity);

            $this->entityManager->saveEntity($stage);
        }
    }
}
