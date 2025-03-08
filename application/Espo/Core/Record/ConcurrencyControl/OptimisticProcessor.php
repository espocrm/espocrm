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

namespace Espo\Core\Record\ConcurrencyControl;

use Espo\Core\Name\Field;
use Espo\Core\Record\ConcurrencyControl\Optimistic\Result;
use Espo\Core\Utils\FieldUtil;
use Espo\ORM\BaseEntity;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @internal
 */
class OptimisticProcessor
{
    public function __construct(
        private EntityManager $entityManager,
        private FieldUtil $fieldUtil,
    ) {}

    public function process(Entity $entity, int $versionNumber): ?Result
    {
        $previousVersionNumber = $entity->getFetched(Field::VERSION_NUMBER);

        if ($previousVersionNumber === null) {
            return null;
        }

        if ($versionNumber === $previousVersionNumber) {
            return null;
        }

        $changedFieldList = [];

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        foreach ($entityDefs->getFieldList() as $fieldDefs) {
            $field = $fieldDefs->getName();

            if (
                $fieldDefs->getParam('optimisticConcurrencyControlIgnore') ||
                $fieldDefs->getParam(FieldParam::READ_ONLY)
            ) {
                continue;
            }

            foreach ($this->fieldUtil->getActualAttributeList($entityDefs->getName(), $field) as $attribute) {
                if (
                    $entity instanceof BaseEntity &&
                    !$entity->isAttributeWritten($attribute)
                ) {
                    continue;
                }

                if (!$entity->hasFetched($attribute)) {
                    continue;
                }

                if ($entity->isAttributeChanged($attribute)) {
                    $changedFieldList[] = $field;

                    continue 2;
                }
            }
        }

        if ($changedFieldList === []) {
            return null;
        }

        $values = (object) [];

        foreach ($changedFieldList as $field) {
            foreach ($this->fieldUtil->getAttributeList($entityDefs->getName(), $field) as $attribute) {
                $values->$attribute = $entity->getFetched($attribute);
            }
        }

        return new Result(
            fieldList: $changedFieldList,
            values: $values,
            versionNumber: $previousVersionNumber,
        );
    }
}
