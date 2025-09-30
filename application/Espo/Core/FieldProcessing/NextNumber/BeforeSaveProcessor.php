<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\FieldProcessing\NextNumber;

use Espo\Core\Exceptions\Error;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\ORM\Type\FieldType;
use Espo\Entities\NextNumber;

use Espo\Core\ORM\Entity;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Metadata;

use const STR_PAD_LEFT;

class BeforeSaveProcessor
{
    /** @var array<string, string[]> */
    private $fieldListMapCache = [];

    public function __construct(
        private Metadata $metadata,
        private EntityManager $entityManager,
    ) {}

    /**
     * For an existing record.
     * @throws Error
     */
    public function processPopulate(Entity $entity, string $field): void
    {
        $fieldList = $this->getFieldList($entity->getEntityType());

        if (!in_array($field, $fieldList)) {
            throw new Error("Bad field.");
        }

        $this->processItem($entity, $field, [], true);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function process(Entity $entity, array $options): void
    {
        $fieldList = $this->getFieldList($entity->getEntityType());

        foreach ($fieldList as $field) {
            $this->processItem($entity, $field, $options);
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function processItem(Entity $entity, string $field, array $options, bool $populate = false): void
    {
        if (!empty($options[SaveOption::IMPORT]) && $entity->has($field)) {
            return;
        }

        if (!$entity->isNew()) {
            if ($entity->isAttributeChanged($field)) {
                $entity->set($field, $entity->getFetched($field));
            }

            if (!$populate) {
                return;
            }
        }

        $this->entityManager->getTransactionManager()->run(function () use ($entity, $field) {
            $nextNumber = $this->getNextNumberEntity($entity, $field);

            $entity->set($field, $this->composeNumberStringValue($nextNumber));

            $nextNumber->setNumberValue($this->prepareNextNumberValue($nextNumber));

            $this->entityManager->saveEntity($nextNumber);
        });
    }

    private function composeNumberStringValue(NextNumber $nextNumber): string
    {
        $entityType = $nextNumber->getTargetEntityType();
        $fieldName = $nextNumber->getTargetFieldName();
        $value = $nextNumber->getNumberValue();

        $prefix = $this->metadata->get(['entityDefs', $entityType, 'fields', $fieldName, 'prefix'], '');
        $padLength = $this->metadata->get(['entityDefs', $entityType, 'fields', $fieldName, 'padLength'], 0);

        return $prefix . str_pad(strval($value), $padLength, '0', STR_PAD_LEFT);
    }

    /**
     * @return string[]
     */
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

            if ($defs->getType() !== FieldType::NUMBER) {
                continue;
            }

            $list[] = $name;
        }

        $this->fieldListMapCache[$entityType] = $list;

        return $list;
    }

    private function prepareNextNumberValue(NextNumber $nextNumber): int
    {
        $value = $nextNumber->getNumberValue();

        if (!$value) {
            $value = 1;
        }

        $value++;

        return $value;
    }

    private function getNextNumberEntity(Entity $entity, string $field): NextNumber
    {
        $nextNumber = $this->entityManager
            ->getRDBRepositoryByClass(NextNumber::class)
            ->where([
                'fieldName' => $field,
                'entityType' => $entity->getEntityType(),
            ])
            ->forUpdate()
            ->findOne();

        if (!$nextNumber) {
            $nextNumber = $this->entityManager->getRDBRepositoryByClass(NextNumber::class)->getNew();

            $nextNumber
                ->setTargetEntityType($entity->getEntityType())
                ->setTargetFieldName($field);
        }

        return $nextNumber;
    }
}
