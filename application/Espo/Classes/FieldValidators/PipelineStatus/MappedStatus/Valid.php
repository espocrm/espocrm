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

namespace Espo\Classes\FieldValidators\PipelineStatus\MappedStatus;

use Espo\Core\Utils\Metadata;
use Espo\Entities\PipelineStage;
use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\ORM\Defs;
use Espo\ORM\Entity;
use Espo\Tools\OpenApi\Util\EnumOptionsProvider;

/**
 * @implements Validator<PipelineStage>
 */
class Valid implements Validator
{
    public function __construct(
        private Metadata $metadata,
        private EnumOptionsProvider $enumOptionsProvider,
        private Defs $defs,
    ) {}

    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        $status = $entity->getMappedStatus();

        $entityType = $entity->getPipeline()->getTargetEntityType();

        $field = $this->metadata->get("scopes.$entityType.statusField");

        if (!$field) {
            return null;
        }

        $fieldDefs = $this->defs
            ->getEntity($entityType)
            ->getField($field);

        $options = $this->enumOptionsProvider->get($fieldDefs);

        if (!$options) {
            return Failure::create();
        }

        if (!in_array($status, $options)) {
            return Failure::create();
        }

        return null;
    }
}
