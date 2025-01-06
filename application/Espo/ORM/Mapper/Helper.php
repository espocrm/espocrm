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

namespace Espo\ORM\Mapper;

use Espo\ORM\Entity;
use Espo\ORM\Metadata;

use Espo\ORM\Name\Attribute;
use RuntimeException;

class Helper
{
    public function __construct(private Metadata $metadata)
    {}

    /**
     * @return array{
     *   key: string,
     *   foreignKey: string,
     *   foreignType?: string,
     *   nearKey?: string,
     *   distantKey?: string,
     *   typeKey?: string,
     * }
     */
    public function getRelationKeys(Entity $entity, string $relationName): array
    {
        $entityType = $entity->getEntityType();

        $defs = $this->metadata->getDefs()
            ->getEntity($entityType)
            ->getRelation($relationName);

        $type = $defs->getType();

        switch ($type) {

            case Entity::BELONGS_TO:
                $key = $defs->hasKey() ?
                    $defs->getKey() :
                    $relationName . 'Id';

                $foreignKey = $defs->hasForeignKey() ?
                    $defs->getForeignKey() :
                    Attribute::ID;

                return [
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                ];

            case Entity::HAS_MANY:
            case Entity::HAS_ONE:
                $key = $defs->hasKey() ? $defs->getKey() : Attribute::ID;

                $foreign = $defs->hasForeignRelationName() ?
                    $defs->getForeignRelationName() :
                    null;

                $foreignKey = $defs->hasForeignKey() ?
                    $defs->getForeignKey() :
                    null;

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
                $key = $defs->hasKey() ? $defs->getKey() : Attribute::ID;

                $foreignKey = $defs->hasForeignKey() ?
                    $defs->getForeignKey() :
                    'parentId';

                $foreignType = $defs->getParam('foreignType') ?? 'parentType';

                return [
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                    'foreignType' => $foreignType,
                ];

            case Entity::MANY_MANY:
                $key = $defs->hasKey() ?
                    $defs->getKey() :
                    Attribute::ID;

                $foreignKey = $defs->hasForeignKey() ?
                    $defs->getForeignKey() :
                    Attribute::ID;

                $nearKey = $defs->hasMidKey() ?
                    $defs->getMidKey() :
                    lcfirst($entityType) . 'Id';

                $distantKey = $defs->hasForeignMidKey() ?
                    $defs->getForeignMidKey() :
                    lcfirst($defs->getForeignEntityType()) . 'Id';

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
                    'foreignKey' => Attribute::ID,
                ];
        }

        throw new RuntimeException("Relation type '{$type}' not supported for 'getKeys'.");
    }
}
