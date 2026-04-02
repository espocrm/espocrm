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

namespace Espo\Tools\Pipeline;

use Espo\Core\Exceptions\Conflict;
use Espo\Entities\PipelineStage;
use Espo\ORM\EntityManager;
use RuntimeException;

class StageMoveService
{
    public function __construct(
        private EntityManager $entityManager,
        private CacheClearer $cacheClearer,
    ) {}

    /**
     * @throws Conflict
     */
    public function moveUp(PipelineStage $stage): void
    {
        $columns = $this->getStages($stage);
        $index = $this->getIndex($columns, $stage);

        $anotherColumn = $columns[$index - 1] ?? null;

        if (!$anotherColumn) {
            throw new Conflict("Can't move first.");
        }

        $this->swapStages($anotherColumn, $stage);

        $this->cacheClearer->clear();
    }

    /**
     * @throws Conflict
     */
    public function moveDown(PipelineStage $column): void
    {
        $columns = $this->getStages($column);
        $index = $this->getIndex($columns, $column);

        $anotherColumn = $columns[$index + 1] ?? null;

        if (!$anotherColumn) {
            throw new Conflict("Can't move last.");
        }

        $this->swapStages($anotherColumn, $column);
    }

    /**
     * @throws Conflict
     */
    private function swapStages(PipelineStage $anotherColumn, PipelineStage $column): void
    {
        if ($anotherColumn->getMappedStatus() !== $column->getMappedStatus()) {
            throw new Conflict("Can't break status order.");
        }

        $order = $column->getOrder();
        $column->setOrder($anotherColumn->getOrder());
        $anotherColumn->setOrder($order);

        $this->entityManager->saveEntity($column);
        $this->entityManager->saveEntity($anotherColumn);
    }

    /**
     * @return PipelineStage[]
     */
    private function getStages(PipelineStage $stage): array
    {
        $collection = $this->entityManager
            ->getRDBRepositoryByClass(PipelineStage::class)
            ->where([PipelineStage::ATTR_PIPELINE_ID => $stage->getPipeline()->getId()])
            ->order(PipelineStage::FIELD_ORDER)
            ->find();

        return iterator_to_array($collection);
    }

    /**
     * @param PipelineStage[] $columns
     */
    private function getIndex(array $columns, PipelineStage $column): int
    {
        $index = -1;

        foreach ($columns as $i => $it) {
            if ($it->getId() === $column->getId()) {
                $index = $i;

                break;
            }
        }

        if ($index < 0) {
            throw new RuntimeException("Stage not found.");
        }

        return $index;
    }
}
