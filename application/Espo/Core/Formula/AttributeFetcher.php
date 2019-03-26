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

namespace Espo\Core\Formula;

use \Espo\ORM\Entity;
use \Espo\Core\Exceptions\Error;

class AttributeFetcher
{
    private $relatedEntitiesCacheMap = [];

    public function __construct()
    {
    }

    public function fetch(Entity $entity, $attribute, $getFetchedAttribute = false)
    {
        if (!is_string($attribute)) {
            throw new Error();
        }

        if (strpos($attribute, '.') !== false) {
            $arr = explode('.', $attribute);

            $key = $this->buildKey($entity, $arr[0]);
            if (!array_key_exists($key, $this->relatedEntitiesCacheMap)) {
                $this->relatedEntitiesCacheMap[$key] = $entity->get($arr[0]);
            }
            $relatedEntity = $this->relatedEntitiesCacheMap[$key];
            if ($relatedEntity && ($relatedEntity instanceof Entity) && count($arr) > 0) {
                return $this->fetch($relatedEntity, $arr[1]);
            }
            return null;
        }

        $methodName = 'get';
        if ($getFetchedAttribute) {
            $methodName = 'getFetched';
        }

        if ($entity->getAttributeParam($attribute, 'isParentName') && $methodName == 'get') {
            $relationName = $entity->getAttributeParam($attribute, 'relation');
            if ($parent = $entity->get($relationName)) {
                return $parent->get('name');
            }
        } else if ($entity->getAttributeParam($attribute, 'isLinkMultipleIdList') && $methodName == 'get') {
            $relationName = $entity->getAttributeParam($attribute, 'relation');
            if (!$entity->has($attribute)) {
                $entity->loadLinkMultipleField($relationName);
            }
        }

        return $entity->$methodName($attribute);
    }

    public function resetRuntimeCache()
    {
        $this->relatedEntitiesCacheMap = [];
    }

    protected function buildKey(Entity $entity, $link)
    {
        return spl_object_hash($entity) . '-' . $link;
    }
}
