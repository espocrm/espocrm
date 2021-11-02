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

namespace Espo\Core\ORM;

use Espo\ORM\BaseEntity;

use LogicException;
use stdClass;

class Entity extends BaseEntity
{
    public function hasLinkMultipleField(string $field): bool
    {
        return
            $this->hasRelation($field) &&
            $this->getAttributeParam($field . 'Ids', 'isLinkMultipleIdList');
    }

    public function hasLinkField(string $field): bool
    {
        return $this->hasAttribute($field . 'Id') && $this->hasRelation($field);
    }

    public function hasLinkParentField(string $field): bool
    {
        return
            $this->getAttributeType($field . 'Type') == 'foreignType' &&
            $this->hasAttribute($field . 'Id') &&
            $this->hasRelation($field);
    }

    public function loadParentNameField(string $field): void
    {
        if (!$this->hasAttribute($field. 'Id') || !$this->hasAttribute($field . 'Type')) {
            throw new LogicException("There's no link-parent field '{$field}'.");
        }

        $parentId = $this->get($field . 'Id');
        $parentType = $this->get($field . 'Type');

        if ($parentId && $parentType) {
            if (!$this->entityManager->hasRepository($parentType)) {
                return;
            }

            $repository = $this->entityManager->getRDBRepository($parentType);

            $select = ['id', 'name'];

            $foreignEntity = $repository
                ->select($select)
                ->where(['id' => $parentId])
                ->findOne();

            if ($foreignEntity) {
                $this->set($field . 'Name', $foreignEntity->get('name'));

                return;
            }

            $this->set($field . 'Name', null);

            return;
        }

        $this->set($field . 'Name', null);
    }

    protected function getRelationOrderParams(string $link): ?array
    {
        $field = $link;

        $idsAttribute = $field . 'Ids';

        $foreignEntityType = $this->getRelationParam($field, 'entity');

        if ($this->getAttributeParam($idsAttribute, 'orderBy')) {
            $defs = [
                'orderBy' => $this->getAttributeParam($idsAttribute, 'orderBy'),
                'order' => 'ASC',
            ];

            if ($this->getAttributeParam($idsAttribute, 'orderDirection')) {
                $defs['order'] = $this->getAttributeParam($idsAttribute, 'orderDirection');
            }

            return $defs;
        }

        if (!$foreignEntityType || !$this->entityManager) {
            return null;
        }

        $ormDefs = $this->entityManager->getMetadata()->getDefs();

        if (!$ormDefs->hasEntity($foreignEntityType)) {
            return null;
        }

        $entityDefs = $ormDefs->getEntity($foreignEntityType);

        $collectionDefs = $entityDefs->getParam('collection') ?? [];

        $orderBy = $collectionDefs['orderBy'] ?? null;
        $order = $collectionDefs['order'] ?? 'ASC';

        if (!$orderBy) {
            return null;
        }

        if (!$entityDefs->hasAttribute($orderBy)) {
            return null;
        }

        return [
            'orderBy' => $orderBy,
            'order' => $order,
        ];
    }

    public function loadLinkMultipleField(string $field, $columns = null): void
    {
        if (!$this->hasRelation($field) || !$this->hasAttribute($field . 'Ids')) {
            return;
            // @todo Throw exception in 6.4.
            // throw new LogicException("There's no link-multiple field '{$field}'.");
        }

        $select = ['id', 'name'];

        $hasType = $this->hasAttribute($field . 'Types');

        if ($hasType) {
            $select[] = 'type';
        }

        if (!empty($columns)) {
            foreach ($columns as $item) {
                $select[] = $item;
            }
        }

        $selectBuilder = $this->entityManager
            ->getRDBRepository($this->getEntityType())
            ->getRelation($this, $field)
            ->select($select);

        $orderParams = $this->getRelationOrderParams($field);

        if ($orderParams) {
            $selectBuilder->order($orderParams['orderBy'], $orderParams['order']);
        }

        $collection = $selectBuilder->find();

        $ids = [];
        $names = (object) [];
        $types = (object) [];
        $columnsData = (object) [];

        foreach ($collection as $e) {
            $id = $e->getId();

            $ids[] = $id;

            $names->$id = $e->get('name');

            if ($hasType) {
                $types->$id = $e->get('type');
            }

            if (!empty($columns)) {
                $columnsData->$id = (object) [];

                foreach ($columns as $column => $f) {
                    $columnsData->$id->$column = $e->get($f);
                }
            }
        }

        $idsAttribute = $field . 'Ids';

        $this->set($idsAttribute, $ids);

        if (!$this->isNew() && !$this->hasFetched($idsAttribute)) {
            $this->setFetched($idsAttribute, $ids);
        }

        $this->set($field . 'Names', $names);

        if ($hasType) {
            $this->set($field . 'Types', $types);
        }

        if (!empty($columns)) {
            $this->set($field . 'Columns', $columnsData);
        }
    }

    public function loadLinkField(string $field): void
    {
        if (!$this->hasRelation($field) || !$this->hasAttribute($field . 'Id')) {
            throw new LogicException("There's no link field '{$field}'.");
        }

        if ($this->getRelationType($field) !== 'hasOne' && $this->getRelationType($field) !== 'belongsTo') {
            throw new LogicException("Can't load link '{$field}'.");
        }

        $select = ['id', 'name'];

        $entity = $this->entityManager
            ->getRDBRepository($this->getEntityType())
            ->getRelation($this, $field)
            ->select($select)
            ->findOne();

        $entityId = null;
        $entityName = null;

        if ($entity) {
            $entityId = $entity->getId();
            $entityName = $entity->get('name');
        }

        $idAttribute = $field . 'Id';

        if (!$this->isNew() && !$this->hasFetched($idAttribute)) {
            $this->setFetched($idAttribute, $entityId);
        }

        $this->set($idAttribute, $entityId);

        $this->set($field . 'Name', $entityName);
    }

    /**
     * @return mixed
     */
    public function getLinkMultipleName(string $field, string $id)
    {
        $namesAttribute = $field . 'Names';

        if (!$this->has($namesAttribute)) {
            return;
        }

        $names = $this->get($namesAttribute);

        if ($names instanceof stdClass && isset($names->$id) && isset($names->$id)) {
            return $names->$id;
        }

        return null;
    }

    public function setLinkMultipleName(string $field, string $id, ?string $value): void
    {
        $namesAttribute = $field . 'Names';

        if (!$this->has($namesAttribute)) {
            return;
        }

        $object = $this->get($namesAttribute);

        if (!isset($object) || !($object instanceof stdClass)) {
            $object = (object) [];
        }

        $object->$id = $value;

        $this->set($namesAttribute, $object);
    }

    /**
     * @return mixed
     */
    public function getLinkMultipleColumn(string $field, string $column, string $id)
    {
        $columnsAttribute = $field . 'Columns';

        if (!$this->has($columnsAttribute)) {
            return null;
        }

        $columns = $this->get($columnsAttribute);

        if ($columns instanceof stdClass && isset($columns->$id) && isset($columns->$id->$column)) {
            return $columns->$id->$column;
        }

        return null;
    }

    public function setLinkMultipleColumn(string $field, string $column, string $id, $value): void
    {
        $columnsAttribute = $field . 'Columns';

        if (!$this->hasAttribute($columnsAttribute)) {
            return;
        }

        $object = $this->get($columnsAttribute);

        if (!isset($object) || !($object instanceof stdClass)) {
            $object = (object) [];
        }

        if (!isset($object->$id)) {
            $object->$id = (object) [];
        }

        if (!isset($object->$id->$column)) {
            $object->$id->$column = (object) [];
        }

        $object->$id->$column = $value;

        $this->set($columnsAttribute, $object);
    }

    public function setLinkMultipleIdList(string $field, array $idList): void
    {
        $idsAttribute = $field . 'Ids';

        $this->set($idsAttribute, $idList);
    }

    public function addLinkMultipleId(string $field, string $id): void
    {
        $idsAttribute = $field . 'Ids';

        if (!$this->hasAttribute($idsAttribute)) {
            return;
        }

        if (!$this->has($idsAttribute)) {
            if (!$this->isNew()) {
                $this->loadLinkMultipleField($field);
            } else {
                $this->set($idsAttribute, []);
            }
        }

        if (!$this->has($idsAttribute)) {
            return;
        }

        $idList = $this->get($idsAttribute);

        if (!in_array($id, $idList)) {
            $idList[] = $id;
            $this->set($idsAttribute, $idList);
        }
    }

    public function removeLinkMultipleId(string $field, string $id): void
    {
        if ($this->hasLinkMultipleId($field, $id)) {
            $list = $this->getLinkMultipleIdList($field);

            $index = array_search($id, $list);
            if ($index !== false) {
                unset($list[$index]);
                $list = array_values($list);
            }

            $this->setLinkMultipleIdList($field, $list);
        }
    }

    public function getLinkMultipleIdList(string $field): ?array
    {
        $idsAttribute = $field . 'Ids';

        if (!$this->hasAttribute($idsAttribute)) {
            return null;
        }

        if (!$this->has($idsAttribute)) {
            if (!$this->isNew()) {
                $this->loadLinkMultipleField($field);
            }
        }

        $valueList = $this->get($idsAttribute);

        if (empty($valueList)) {
            return [];
        }

        return $valueList;
    }

    public function hasLinkMultipleId(string $field, string $id): bool
    {
        $idsAttribute = $field . 'Ids';

        if (!$this->hasAttribute($idsAttribute)) {
            return false;
        }

        if (!$this->has($idsAttribute)) {
            if (!$this->isNew()) {
                $this->loadLinkMultipleField($field);
            }
        }

        if (!$this->has($idsAttribute)) {
            return false;
        }

        $idList = $this->get($idsAttribute);

        if (in_array($id, $idList)) {
            return true;
        }

        return false;
    }
}
