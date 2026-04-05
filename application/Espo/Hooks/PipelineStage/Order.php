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

namespace Espo\Hooks\PipelineStage;

use Espo\Core\Hook\Hook\AfterRemove;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Entities\Pipeline;
use Espo\Entities\PipelineStage;
use Espo\Tools\Pipeline\MoveService;
use Espo\ORM\Collection;
use Espo\ORM\Defs;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\RemoveOptions;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Tools\OpenApi\Util\EnumOptionsProvider;

/**
 * @implements BeforeSave<PipelineStage>
 * @implements AfterRemove<PipelineStage>
 */
class Order implements BeforeSave, AfterRemove
{
    public function __construct(
        private EntityManager $entityManager,
        private MoveService $moveService,
        private Defs $defs,
        private EnumOptionsProvider $enumOptionsProvider,
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isNew()) {
            return;
        }

        $stages = $this->getStages($entity);

        $i = 0;
        $newList = [];

        foreach ($this->getStatusList($entity) as $status) {
            foreach ($stages as $stage) {
                if ($stage->getMappedStatus() === $status) {
                    $newList[] = $stage;

                    $stage->setOrder($i);
                    $i++;
                }
            }

            if ($entity->getMappedStatus() === $status) {
                $newList[] = $entity;

                $entity->setOrder($i);
                $i++;
            }
        }

        foreach ($newList as $stage) {
            if ($stage->getId() === $entity->getId()) {
                continue;
            }

            $this->entityManager->saveEntity($stage, [SaveOption::SILENT => true]);
        }
    }

    public function afterRemove(Entity $entity, RemoveOptions $options): void
    {
        $this->moveService->reOrder($entity::class, $entity->get(PipelineStage::ATTR_PIPELINE_ID));
    }

    /**
     * @return iterable<PipelineStage>
     */
    private function getStages(PipelineStage $entity): iterable
    {
        /** @var Collection<PipelineStage> */
        return $this->entityManager
            ->getRelation($entity->getPipeline(), Pipeline::LINK_STAGES)
            ->order(PipelineStage::FIELD_ORDER)
            ->find();
    }

    /**
     * @return string[]
     */
    private function getStatusList(PipelineStage $entity): array
    {
        $targetEntityType = $entity->getPipeline()->getTargetEntityType();
        $field = $entity->getPipeline()->getTargetField();

        $fieldDefs = $this->defs
            ->getEntity($targetEntityType)
            ->getField($field);

        return $this->enumOptionsProvider->get($fieldDefs) ?? [];
    }
}
