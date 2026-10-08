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

namespace Espo\Repositories;

use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Entities\ArrayValue as ArrayValueEntity;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Entity;
use Espo\Core\Repositories\Database;
use Espo\ORM\Name\Attribute;
use RuntimeException;
use LogicException;

/**
 * @extends Database<ArrayValueEntity>
 */
class ArrayValue extends Database
{
    private const int ITEM_MAX_LENGTH = 100;

    public function storeEntityAttribute(CoreEntity $entity, string $attribute, bool $populateMode = false): void
    {
        if ($entity->getAttributeType($attribute) !== Entity::JSON_ARRAY) {
            throw new LogicException("ArrayValue: Can't store non array attribute.");
        }

        if (
            $entity->getAttributeParam($attribute, AttributeParam::NOT_STORABLE) ||
            !$entity->getAttributeParam($attribute, 'storeArrayValues') ||
            !$entity->has($attribute)
        ) {
            return;
        }

        $valueList = $entity->get($attribute);

        if (is_null($valueList)) {
            $valueList = [];
        }

        if (!is_array($valueList)) {
            throw new RuntimeException("ArrayValue: Bad value passed to JSON_ARRAY attribute '$attribute'.");
        }

        $valueList = array_unique($valueList);

        if (!$entity->isNew() && !$populateMode) {
            $this->entityManager->getTransactionManager()
                ->run(
                    fn () => $this->storeInternal(
                        entity: $entity,
                        populateMode: $populateMode,
                        attribute: $attribute,
                        valueList: $valueList,
                    )
                );

            return;
        }

        $this->storeInternal(
            entity: $entity,
            populateMode: $populateMode,
            attribute: $attribute,
            valueList: $valueList,
        );
    }

    /**
     * @param string[] $valueList
     */
    private function storeInternal(
        CoreEntity $entity,
        bool $populateMode,
        string $attribute,
        array $valueList,
    ): void {

        $toSkipValueList = [];

        if (!$entity->isNew() && !$populateMode) {
            $existingList = $this
                ->select([Attribute::ID, ArrayValueEntity::FIELD_VALUE])
                ->where([
                    'entityType' => $entity->getEntityType(),
                    'entityId' => $entity->getId(),
                    'attribute' => $attribute,
                ])
                ->forUpdate()
                ->find();

            foreach ($existingList as $existing) {
                if (!in_array($existing->getValue(), $valueList)) {
                    $this->deleteFromDb($existing->getId());

                    continue;
                }

                $toSkipValueList[] = $existing->getValue();
            }
        }

        $itemMaxLength = $this->entityManager
            ->getDefs()
            ->getEntity(ArrayValueEntity::ENTITY_TYPE)
            ->getField(ArrayValueEntity::FIELD_VALUE)
            ->getParam(FieldParam::MAX_LENGTH) ?? self::ITEM_MAX_LENGTH;

        foreach ($valueList as $value) {
            if (in_array($value, $toSkipValueList)) {
                continue;
            }

            if (!is_string($value)) {
                continue;
            }

            if (mb_strlen($value) > $itemMaxLength) {
                $value = mb_substr($value, 0, $itemMaxLength);
            }

            $arrayValue = $this->getNew();

            $arrayValue->setMultiple([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
                'attribute' => $attribute,
                ArrayValueEntity::FIELD_VALUE => $value,
            ]);

            $this->save($arrayValue);
        }
    }

    public function deleteEntityAttribute(CoreEntity $entity, string $attribute): void
    {
        if (!$entity->hasId()) {
            throw new LogicException("ArrayValue: Can't delete '$attribute' w/o id given.");
        }

        $this->entityManager->getTransactionManager()
            ->run(fn () => $this->deleteEntityAttributeInternal($entity, $attribute));
    }


    private function deleteEntityAttributeInternal(CoreEntity $entity, string $attribute): void
    {
        $list = $this
            ->select([Attribute::ID])
            ->forUpdate()
            ->where([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
                'attribute' => $attribute,
            ])
            ->find();

        foreach ($list as $arrayValue) {
            $this->deleteFromDb($arrayValue->getId());
        }
    }
}
