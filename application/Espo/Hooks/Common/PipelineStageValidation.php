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

namespace Espo\Hooks\Common;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\Name\Field;
use Espo\Entities\PipelineStage;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Exceptions\ValidationException;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Tools\Pipeline\MetadataProvider;

/**
 * @noinspection PhpUnused
 */
class PipelineStageValidation implements BeforeSave
{
    public function __construct(
        private MetadataProvider $metadataProvider,
        private EntityManager $entityManager,
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (!$this->metadataProvider->isEnabled($entity->getEntityType())) {
            return;
        }

        if (
            !$entity->isAttributeChanged(Field::PIPELINE . 'Id') &&
            !$entity->isAttributeChanged(Field::PIPELINE_STAGE . 'Id')
        ) {
            return;
        }

        $pipelineId = $entity->get(Field::PIPELINE . 'Id');
        $stageId = $entity->get(Field::PIPELINE_STAGE . 'Id');

        if (!$stageId) {
            return;
        }

        $stage = $this->entityManager->getRDBRepositoryByClass(PipelineStage::class)->getById($stageId);

        if (!$stage) {
            throw new ValidationException("Pipeline stage '$stageId' does not exist.");
        }

        if ($stage->getPipeline()->getId() !== $pipelineId) {
            throw new ValidationException("Pipeline stage does not belong to the set pipelined.");
        }
    }
}
