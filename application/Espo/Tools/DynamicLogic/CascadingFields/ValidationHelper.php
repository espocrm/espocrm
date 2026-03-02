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

namespace Espo\Tools\DynamicLogic\CascadingFields;

use Espo\Core\FieldValidation\Validator\Failure;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\ORM\Type\FieldType;
use Espo\ORM\Defs;

/**
 * @internal
 */
class ValidationHelper
{
    public function __construct(
        private Defs $defs,
    ) {}

    public function validateItem(CoreEntity $entity, CoreEntity $valueEntity, Item $item): ?Failure
    {
        if (!$item->matchRequired) {
            return null;
        }

        $localIds = $this->getIds($entity, $item->localField);
        $foreignIds = $this->getIds($valueEntity, $item->foreignField) ?? [];

        if (!$localIds) {
            if ($foreignIds) {
                return Failure::create();
            }

            return null;
        }

        if ($this->getType($valueEntity, $item->foreignField) === FieldType::LINK_MULTIPLE) {
            if (!array_diff($localIds, $foreignIds)) {
                return null;
            }

            return Failure::create();
        }

        if (array_intersect($localIds, $foreignIds)) {
            return null;
        }

        return Failure::create();
    }

    /**
     * @return ?string[]
     */
    private function getIds(CoreEntity $entity, string $field): ?array
    {
        $type = $this->getType($entity, $field);

        if ($type === FieldType::LINK_MULTIPLE) {
            return $entity->getLinkMultipleIdList($field);
        }

        $localId = $entity->get($field . 'Id');

        if (!$localId) {
            return null;
        }

        return [$localId];
    }


    private function getType(CoreEntity $entity, string $field): ?string
    {
        return $this->defs
            ->getEntity($entity->getEntityType())
            ->tryGetField($field)
            ?->getType();
    }
}
