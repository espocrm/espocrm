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

namespace Espo\Core\FieldProcessing\Relation;

use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Core\FieldProcessing\Saver\Params;
use Espo\Core\ORM\EntityManager;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\ORM\Defs\Params\RelationParam;

/**
 * Saves a link-multiple field or has-many relation set in a link stub attribute.
 * In case of a stab attribute, only processed for a new record.
 */
class LinkMultipleSaver
{
    private const RELATE_OPTION = 'linkMultiple';

    public function __construct(private EntityManager $entityManager)
    {}

    public function process(CoreEntity $entity, string $name, Params $params): void
    {
        $idListAttribute = $name . 'Ids';
        $columnsAttribute = $name . 'Columns';

        if (
            !$entity->isNew() &&
            !$entity->hasLinkMultipleField($name)
        ) {
            $entity->clear($idListAttribute);

            return;
        }

        $isChanged = $entity->isAttributeChanged($idListAttribute) || $entity->isAttributeChanged($columnsAttribute);

        if (!$isChanged) {
            return;
        }

        $defs = $this->entityManager->getDefs()->getEntity($entity->getEntityType());

        $skipCreate = $params->getOption('skipLinkMultipleCreate') ?? false;
        $skipRemove = $params->getOption('skipLinkMultipleRemove') ?? false;
        $skipUpdate = $params->getOption('skipLinkMultipleUpdate') ?? false;
        $skipHooks = $params->getOption('skipLinkMultipleHooks') ?? false;

        if ($entity->isNew()) {
            $skipRemove = true;
            $skipUpdate = true;
        }

        if ($entity->has($idListAttribute)) {
            $specifiedIdList = $entity->get($idListAttribute);
        } else if ($entity->has($columnsAttribute)) {
            $skipRemove = true;

            $specifiedIdList = array_keys(
                get_object_vars(
                    $entity->get($columnsAttribute) ?? (object) []
                )
            );
        } else {
            return;
        }

        if (!is_array($specifiedIdList)) {
            return;
        }

        $toRemoveIdList = [];
        $existingIdList = [];
        $toUpdateIdList = [];
        $toCreateIdList = [];

        $existingColumnsData = (object) [];

        $columns = null;

        if ($defs->hasField($name)) {
            $columns = $defs->getField($name)->getParam('columns');
        }

        $allColumns = $columns;

        if (is_array($columns)) {
            $additionalColumns = $defs->getRelation($name)->getParam(RelationParam::ADDITIONAL_COLUMNS) ?? [];

            foreach ($columns as $column => $field) {
                if (!array_key_exists($column, $additionalColumns)) {
                    unset($columns[$column]);
                }
            }
        }

        $repository = $this->entityManager->getRDBRepository($entity->getEntityType());

        $columnData = !empty($columns) ?
            $entity->get($columnsAttribute) :
            null;

        if (!$skipRemove || !$skipUpdate) {
            $foreignEntityList = $repository->getRelation($entity, $name)->find();

            foreach ($foreignEntityList as $foreignEntity) {
                $existingIdList[] = $foreignEntity->getId();

                if (empty($allColumns)) {
                    continue;
                }

                $data = (object) [];

                $foreignId = $foreignEntity->getId();

                foreach ($allColumns as $columnName => $columnField) {
                    $data->$columnName = $foreignEntity->get($columnField);
                }

                $existingColumnsData->$foreignId = $data;

                if (!$entity->isNew()) {
                    $entity->setFetched($columnsAttribute, $existingColumnsData);
                }
            }
        }

        if (!$entity->isNew()) {
            if ($entity->has($idListAttribute) && !$entity->hasFetched($idListAttribute)) {
                $entity->setFetched($idListAttribute, $existingIdList);
            }

            if ($entity->has($columnsAttribute) && !empty($allColumns)) {
                $entity->setFetched($columnsAttribute, $existingColumnsData);
            }
        }

        foreach ($existingIdList as $id) {
            if (!in_array($id, $specifiedIdList)) {
                if (!$skipRemove) {
                    $toRemoveIdList[] = $id;
                }

                continue;
            }

            if ($skipUpdate || empty($columns)) {
                continue;
            }

            foreach ($columns as $columnName => $columnField) {
                if (!isset($columnData->$id) || !is_object($columnData->$id)) {
                    continue;
                }

                if (
                    property_exists($columnData->$id, $columnName) &&
                    (
                        !property_exists($existingColumnsData->$id, $columnName) ||
                        $columnData->$id->$columnName !== $existingColumnsData->$id->$columnName
                    )
                ) {
                    $toUpdateIdList[] = $id;
                }
            }
        }

        if (!$skipCreate) {
            foreach ($specifiedIdList as $id) {
                if (!in_array($id, $existingIdList)) {
                    $toCreateIdList[] = $id;
                }
            }
        }

        foreach ($toCreateIdList as $id) {
            $data = null;

            if (is_array($columns) && isset($columnData->$id)) {
                $data = (array) $columnData->$id;

                foreach ($data as $column => $v) {
                    if (!array_key_exists($column, $columns)) {
                        unset($data[$column]);
                    }
                }
            }

            $repository->getRelation($entity, $name)->relateById($id, $data, [
                SaveOption::SKIP_HOOKS => $skipHooks,
                SaveOption::SILENT => $entity->isNew(),
                self::RELATE_OPTION => $entity->hasLinkMultipleField($name),
            ]);
        }

        foreach ($toRemoveIdList as $id) {
            $repository->getRelation($entity, $name)->unrelateById($id, [
                SaveOption::SKIP_HOOKS => $skipHooks,
                self::RELATE_OPTION => $entity->hasLinkMultipleField($name),
            ]);
        }

        foreach ($toUpdateIdList as $id) {
            $data = (array) $columnData->$id;

            if (is_array($columns)) {
                foreach ($data as $column => $v) {
                    if (!array_key_exists($column, $columns)) {
                        unset($data[$column]);
                    }
                }
            }

            $repository->getRelation($entity, $name)->updateColumnsById($id, $data);
        }
    }
}
