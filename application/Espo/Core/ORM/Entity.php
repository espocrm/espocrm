<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class Entity extends \Espo\ORM\Entity
{
    public function hasLinkMultipleField($field)
    {
        return
            $this->hasRelation($field) &&
            $this->getAttributeParam($field . 'Ids', 'isLinkMultipleIdList');
    }

    public function hasLinkField($field)
    {
        return $this->hasAttribute($field . 'Id') && $this->hasRelation($field);
    }

    public function hasLinkParentField($field)
    {
        return
            $this->hasAttributeType($field . 'Type') == 'foreignType' &&
            $this->hasAttribute($field . 'Id') &&
            $this->hasRelation($field);
    }

    public function loadParentNameField($field)
    {
        if (!$this->hasAttribute($field. 'Id') || !$this->hasAttribute($field . 'Type')) return;

        $parentId = $this->get($field . 'Id');
        $parentType = $this->get($field . 'Type');

        if ($parentId && $parentType) {
            if (!$this->entityManager->hasRepository($parentType)) return;
            $repository = $this->entityManager->getRepository($parentType);

            $select = ['id', 'name'];
            $foreignEntity = $repository->select($select)->where(['id' => $parentId])->findOne();
            if ($foreignEntity) {
                $this->set($field . 'Name', $foreignEntity->get('name'));
            } else {
                $this->set($field . 'Name', null);
            }
        } else {
            $this->set($field . 'Name', null);
        }
    }

    public function getLinkMultipleCollection($field)
    {
        if (!$this->hasLinkMultipleField($field)) return;

        $defs = $this->getRelationSelectParams($field);

        $columnAttribute = $field . 'Columns';
        if ($this->hasAttribute($columnAttribute) && $this->getAttributeParam($columnAttribute, 'columns')) {
            $defs['additionalColumns'] = $this->getAttributeParam($columnAttribute, 'columns');
        }

        $collection = $this->get($field, $defs);

        return $collection;
    }

    protected function getRelationSelectParams($link)
    {
        $field = $link;

        $defs = [];

        $idsAttribute = $field . 'Ids';

        $foreignEntityType = $this->getRelationParam($field, 'entity');

        if ($this->getAttributeParam($idsAttribute, 'orderBy')) {
            $defs['orderBy'] = $this->getAttributeParam($idsAttribute, 'orderBy');
            $defs['order'] = 'ASC';
            if ($this->getAttributeParam($idsAttribute, 'orderDirection')) {
                $defs['order'] = $this->getAttributeParam($idsAttribute, 'orderDirection');
            }
        } else {
            if ($foreignEntityType && $this->entityManager) {
                $foreignEntityDefs = $this->entityManager->getMetadata()->get($foreignEntityType);
                if ($foreignEntityDefs && !empty($foreignEntityDefs['collection'])) {
                    $collectionDefs = $foreignEntityDefs['collection'];
                    if (!empty($foreignEntityDefs['collection']['orderBy'])) {
                        $orderBy = $foreignEntityDefs['collection']['orderBy'];
                        $order = 'ASC';
                        if (array_key_exists('order', $foreignEntityDefs['collection'])) {
                            $order = $foreignEntityDefs['collection']['order'];
                        }
                        if (array_key_exists($orderBy, $foreignEntityDefs['fields'])) {
                            $defs['orderBy'] = $orderBy;
                            $defs['order'] = $order;
                        }
                    }
                }
            }
        }

        return $defs;
    }

    public function loadLinkMultipleField($field, $columns = null)
    {
        if (!$this->hasRelation($field) || !$this->hasAttribute($field . 'Ids')) return;

        $defs = $this->getRelationSelectParams($field);

        if (!empty($columns)) {
            $defs['additionalColumns'] = $columns;
        }

        $defs['select'] = ['id', 'name'];

        $hasType = false;
        if ($this->hasField($field . 'Types')) {
            $hasType = true;
            $defs['select'][] = 'type';
        }

        $collection = $this->get($field, $defs);
        $ids = [];
        $names = (object) [];
        $types = (object) [];
        if (!empty($columns)) {
            $columnsData = (object) [];
        }

        if ($collection) {
            foreach ($collection as $e) {
                $id = $e->id;
                $ids[] = $id;
                $names->$id = $e->get('name');
                if ($hasType) {
                    $types->$id = $e->get('type');
                }
                if (!empty($columns)) {
                    $columnsData->$id = new \stdClass();
                    foreach ($columns as $column => $f) {
                        $columnsData->$id->$column = $e->get($f);
                    }
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

    public function loadLinkField($field)
    {
        if (!$this->hasRelation($field) || !$this->hasAttribute($field . 'Id')) return;
        if ($this->getRelationType($field) !== 'hasOne' && $this->getRelationType($field) !== 'belongsTo') return;

        $relatedEntityType = $this->getRelationParam($field, 'entity');

        $select = ['id', 'name'];

        $entity = $this->get($field, [
            'select' => $select
        ]);

        $entityId = null;
        $entityName = null;
        if ($entity) {
            $entityId = $entity->id;
            $entityName = $entity->get('name');
        }

        $idAttribute = $field . 'Id';

        if (!$this->isNew() && !$this->hasFetched($idAttribute)) {
            $this->setFetched($idAttribute, $entityId);
        }

        $this->set($idAttribute, $entityId);
        $this->set($field . 'Name', $entityName);
    }

    public function getLinkMultipleName($field, $id)
    {
        $namesAttribute = $field . 'Names';
        if (!$this->has($namesAttribute)) return;

        $names = $this->get($namesAttribute);
        if ($names instanceof \StdClass) {
            if (isset($names->$id)) {
                if (isset($names->$id)) {
                    return $names->$id;
                }
            }
        }
    }

    public function setLinkMultipleName($field, $id, $value)
    {
        $namesAttribute = $field . 'Names';
        if (!$this->has($namesAttribute)) return;

        $object = $this->get($namesAttribute);
        if (!isset($object) || !($object instanceof \StdClass)) {
            $object = (object) [];
        }

        $object->$id = $value;
        $this->set($namesAttribute, $object);
    }

    public function getLinkMultipleColumn($field, $column, $id)
    {
        $columnsAttribute = $field . 'Columns';

        if (!$this->has($columnsAttribute)) {
            return;
        }
        $columns = $this->get($columnsAttribute);
        if ($columns instanceof \StdClass) {
            if (isset($columns->$id)) {
                if (isset($columns->$id->$column)) {
                    return $columns->$id->$column;
                }
            }
        }
    }

    public function setLinkMultipleColumn($field, $column, $id, $value)
    {
        $columnsAttribute = $field . 'Columns';
        if (!$this->hasAttribute($columnsAttribute)) {
            return;
        }
        $object = $this->get($columnsAttribute);
        if (!isset($object) || !($object instanceof \StdClass)) {
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

    public function setLinkMultipleIdList($field, array $idList)
    {
        $idsAttribute = $field . 'Ids';
        $this->set($idsAttribute, $idList);
    }

    public function addLinkMultipleId($field, $id)
    {
        $idsAttribute = $field . 'Ids';

        if (!$this->hasAttribute($idsAttribute)) return;

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

    public function removeLinkMultipleId($field, $id)
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

    public function getLinkMultipleIdList($field)
    {
        $idsAttribute = $field . 'Ids';

        if (!$this->hasAttribute($idsAttribute)) return null;

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

    public function hasLinkMultipleId($field, $id)
    {
        $idsAttribute = $field . 'Ids';

        if (!$this->hasAttribute($idsAttribute)) return null;

        if (!$this->has($idsAttribute)) {
            if (!$this->isNew()) {
                $this->loadLinkMultipleField($field);
            }
        }

        if (!$this->has($idsAttribute)) {
            return;
        }

        $idList = $this->get($idsAttribute);
        if (in_array($id, $idList)) {
            return true;
        }
        return false;
    }
}
