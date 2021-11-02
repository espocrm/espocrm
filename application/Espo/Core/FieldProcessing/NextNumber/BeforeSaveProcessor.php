<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\FieldProcessing\NextNumber;

use Espo\Entities\NextNumber;

use Espo\Core\{
    ORM\Entity,
    ORM\EntityManager,
    Utils\Metadata,
};

use const STR_PAD_LEFT;

class BeforeSaveProcessor
{
    private $metadata;

    private $entityManager;

    private $fieldListMapCache = [];

    public function __construct(Metadata $metadata, EntityManager $entityManager)
    {
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
    }

    public function process(Entity $entity, array $options): void
    {
        $fieldList = $this->getFieldList($entity->getEntityType());

        foreach ($fieldList as $field) {
            $this->processItem($entity, $field, $options);
        }
    }

    private function processItem(Entity $entity, string $field, array $options): void
    {
        if (!empty($options['import'])) {
            if ($entity->has($field)) {
                return;
            }
        }

        if (!$entity->isNew()) {
            if ($entity->isAttributeChanged($field)) {
                $entity->set($field, $entity->getFetched($field));
            }

            return;
        }

        $this->entityManager->getTransactionManager()->start();

        $nextNumber = $this->entityManager
            ->getRDBRepository('NextNumber')
            ->where([
                'fieldName' => $field,
                'entityType' => $entity->getEntityType(),
            ])
            ->forUpdate()
            ->findOne();

        if (!$nextNumber) {
            $nextNumber = $this->entityManager->getEntity('NextNumber');

            $nextNumber->set('entityType', $entity->getEntityType());
            $nextNumber->set('fieldName', $field);
        }

        $entity->set($field, $this->composeNumberAttribute($nextNumber));

        $value = $nextNumber->get('value');

        if (!$value) {
            $value = 1;
        }

        $value++;

        $nextNumber->set('value', $value);

        $this->entityManager->saveEntity($nextNumber);

        $this->entityManager->getTransactionManager()->commit();
    }

    private function composeNumberAttribute(NextNumber $nextNumber): string
    {
        $entityType = $nextNumber->get('entityType');
        $fieldName = $nextNumber->get('fieldName');
        $value = $nextNumber->get('value');

        $prefix = $this->metadata->get(['entityDefs', $entityType, 'fields', $fieldName, 'prefix'], '');
        $padLength = $this->metadata->get(['entityDefs', $entityType, 'fields', $fieldName, 'padLength'], 0);

        return $prefix . str_pad(strval($value), $padLength, '0', STR_PAD_LEFT);
    }

    private function getFieldList(string $entityType): array
    {
        if (array_key_exists($entityType, $this->fieldListMapCache)) {
            return $this->fieldListMapCache[$entityType];
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $list = [];

        foreach ($entityDefs->getFieldNameList() as $name) {
            $defs = $entityDefs->getField($name);

            if ($defs->getType() !== 'number') {
                continue;
            }

            $list[] = $name;
        }

        $this->fieldListMapCache[$entityType] = $list;

        return $list;
    }
}
