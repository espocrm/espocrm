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
    private const ITEM_MAX_LENGTH = 100;

    public function storeEntityAttribute(CoreEntity $entity, string $attribute, bool $populateMode = false): void
    {
        if ($entity->getAttributeType($attribute) !== Entity::JSON_ARRAY) {
            throw new LogicException("ArrayValue: Can't store non array attribute.");
        }

        if ($entity->getAttributeParam($attribute, AttributeParam::NOT_STORABLE)) {
            return;
        }

        if (!$entity->getAttributeParam($attribute, 'storeArrayValues')) {
            return;
        }

        if (!$entity->has($attribute)) {
            return;
        }

        $valueList = $entity->get($attribute);

        if (is_null($valueList)) {
            $valueList = [];
        }

        if (!is_array($valueList)) {
            throw new RuntimeException("ArrayValue: Bad value passed to JSON_ARRAY attribute {$attribute}.");
        }

        $valueList = array_unique($valueList);
        $toSkipValueList = [];

        $isTransaction = false;

        if (!$entity->isNew() && !$populateMode) {
            $this->entityManager->getTransactionManager()->start();

            $isTransaction = true;

            $existingList = $this
                ->select([Attribute::ID, 'value'])
                ->where([
                    'entityType' => $entity->getEntityType(),
                    'entityId' => $entity->getId(),
                    'attribute' => $attribute,
                ])
                ->forUpdate()
                ->find();

            foreach ($existingList as $existing) {
                if (!in_array($existing->get('value'), $valueList)) {
                    $this->deleteFromDb($existing->getId());

                    continue;
                }

                $toSkipValueList[] = $existing->get('value');
            }
        }

        $itemMaxLength = $this->entityManager
            ->getDefs()
            ->getEntity(ArrayValueEntity::ENTITY_TYPE)
            ->getField('value')
            ->getParam(FieldParam::MAX_LENGTH) ?? self::ITEM_MAX_LENGTH;

        foreach ($valueList as $value) {
            if (in_array($value, $toSkipValueList)) {
                continue;
            }

            if (!is_string($value)) {
                continue;
            }

            if (strlen($value) > $itemMaxLength) {
                $value = substr($value, 0, $itemMaxLength);
            }

            $arrayValue = $this->getNew();

            $arrayValue->set([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
                'attribute' => $attribute,
                'value' => $value,
            ]);

            $this->save($arrayValue);
        }

        if ($isTransaction) {
            $this->entityManager->getTransactionManager()->commit();
        }
    }

    public function deleteEntityAttribute(CoreEntity $entity, string $attribute): void
    {
        if (!$entity->hasId()) {
            throw new LogicException("ArrayValue: Can't delete {$attribute} w/o id given.");
        }

        $this->entityManager->getTransactionManager()->start();

        $list = $this
            ->select([Attribute::ID])
            ->where([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
                'attribute' => $attribute,
            ])
            ->forUpdate()
            ->find();

        foreach ($list as $arrayValue) {
            $this->deleteFromDb($arrayValue->getId());
        }

        $this->entityManager->getTransactionManager()->commit();
    }
}
