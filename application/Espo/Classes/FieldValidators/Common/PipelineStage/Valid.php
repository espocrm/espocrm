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

namespace Espo\Classes\FieldValidators\Common\PipelineStage;

use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\Core\Name\Field;
use Espo\Entities\PipelineStage;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements Validator<Entity>
 */
class Valid implements Validator
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        $pipelineId = $entity->get(Field::PIPELINE . 'Id');
        $stageId = $entity->get(Field::PIPELINE_STAGE . 'Id');

        if (!$pipelineId && $stageId) {
            return Failure::create();
        }

        $stage = $this->entityManager->getRDBRepositoryByClass(PipelineStage::class)->getById($stageId);

        if (!$stage) {
            return Failure::create();
        }

        if ($stage->getPipeline()->getId() !== $pipelineId) {
            return Failure::create();
        }

        return null;
    }
}
