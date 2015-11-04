<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace Espo\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\NotFound;

class RecordTree extends Record
{
    const MAX_DEPTH = 2;

    private $seed = null;

    public function getTree($parentId = null, $params = array(), $level = 0, $maxDepth = null)
    {
        if (!$maxDepth) {
            $maxDepth = self::MAX_DEPTH;
        }

        if ($level == self::MAX_DEPTH) {
            return null;
        }

        $selectParams = $this->getSelectParams($params);
        $selectParams['whereClause'][] = array(
            'parentId' => $parentId
        );

        if ($this->hasOrder()) {
            $selectParams['orderBy'] = [
                ['order', 'asc'],
                ['name', 'asc']
            ];
        } else {
            $selectParams['orderBy'] = [
                ['name', 'asc']
            ];
        }

        $collection = $this->getRepository()->find($selectParams);
        foreach ($collection as $entity) {
            $childList = $this->getTree($entity->id, $params, $level + 1);
            $entity->set('childList', $childList);
        }
        return $collection;
    }

    public function getTreeItemPath($parentId = null)
    {
        $arr = [];
        while (1) {
            if (empty($parentId)) {
                break;
            }
            $parent = $this->getEntityManager()->getEntity($this->entityType, $parentId);
            if ($parent) {
                $parentId = $parent->get('parentId');
                array_unshift($arr, $parent->id);
            } else {
                $parentId = null;
            }
        }
        return $arr;
    }

    protected function getSeed()
    {
        if (empty($this->seed)) {
            $this->seed = $this->getEntityManager()->getEntity($this->getEntityType());
        }
        return $this->seed;
    }

    protected function hasOrder()
    {
        $seed = $this->getSeed();
        if ($seed->hasField('order')) {
            return true;
        }
        return false;
    }

    protected function beforeCreate(Entity $entity, array $data = array())
    {
        parent::beforeCreate($entity, $data);
        if (!empty($data['parentId'])) {
            $parent = $this->getEntityManager()->getEntity($this->getEntityType(), $data['parentId']);
            if (!$parent) {
                throw new Error("Tried to create tree item entity with not existing parent.");
            }
            if (!$this->getAcl()->check($parent, 'edit')) {
                throw new Forbidden();
            }
        }
    }

    public function updateEntity($id, $data)
    {
        if (!empty($data['parentId']) && $data['parentId'] == $id) {
            throw new Forbidden();
        }

        return parent::updateEntity($id, $data);
    }

    public function linkEntity($id, $link, $foreignId)
    {
        if ($id == $foreignId ) {
            throw new Forbidden();
        }
        return parent::linkEntity($id, $link, $foreignId);
    }
}

