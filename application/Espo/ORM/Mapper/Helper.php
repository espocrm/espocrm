<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\ORM\Mapper;

use Espo\ORM\{
    Entity,
    Metadata,
};

use RuntimeException;

class Helper
{
    protected $metadata;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function getRelationKeys(Entity $entity, string $relationName) : array
    {
        if (!$entity->hasRelation($relationName)) {
            throw new RuntimeException("Relation '{$relationName}' does not exist.");
        }

        $params = $entity->getRelations()[$relationName];

        $type = $params['type'] ?? null;

        switch ($type) {

            case Entity::BELONGS_TO:
                $key = $params['key'] ?? ($relationName . 'Id');

                $foreignKey = 'id';

                if (isset($params['foreignKey'])){
                    $foreignKey = $params['foreignKey'];
                }

                return [
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                ];

            case Entity::HAS_MANY:
            case Entity::HAS_ONE:
                $key = $params['key'] ?? 'id';

                $foreign = $params['foreign'] ?? null;

                $foreignKey = $params['foreignKey'] ?? null;

                if (!$foreignKey && $foreign) {
                    $foreignKey = $foreign . 'Id';
                }

                if (!$foreignKey) {
                    $foreignKey = lcfirst($entity->getEntityType()) . 'Id';
                }

                return [
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                ];

            case Entity::HAS_CHILDREN:
                $key = 'id';

                if (isset($params['key'])){
                    $key = $params['key'];
                }

                $foreignKey = 'parentId';

                if (isset($params['foreignKey'])) {
                    $foreignKey = $params['foreignKey'];
                }

                $foreignType = 'parentType';

                if (isset($params['foreignType'])) {
                    $foreignType = $params['foreignType'];
                }

                return [
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                    'foreignType' => $foreignType,
                ];

            case Entity::MANY_MANY:
                $key = 'id';

                if (isset($params['key'])){
                    $key = $params['key'];
                }

                $foreignKey = 'id';

                if (isset($params['foreignKey'])){
                    $foreignKey = $params['foreignKey'];
                }

                $nearKey = lcfirst($entity->getEntityType()) . 'Id';
                $distantKey = lcfirst($params['entity']) . 'Id';

                if (isset($params['midKeys']) && is_array($params['midKeys'])){
                    $nearKey = $params['midKeys'][0];
                    $distantKey = $params['midKeys'][1];
                }

                return [
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                    'nearKey' => $nearKey,
                    'distantKey' => $distantKey,
                ];

            case Entity::BELONGS_TO_PARENT:
                $key = $relationName . 'Id';
                $typeKey = $relationName . 'Type';

                return [
                    'key' => $key,
                    'typeKey' => $typeKey,
                    'foreignKey' => 'id',
                ];
        }

        throw new RuntimeException("Relation type '{$type}' not supported for 'getKeys'.");
    }
}
