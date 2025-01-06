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

namespace Espo\Classes\FieldDuplicators;

use Espo\Core\Record\Duplicator\FieldDuplicator;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use stdClass;

class LinkMultiple implements FieldDuplicator
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function duplicate(Entity $entity, string $field): stdClass
    {
        $valueMap = (object) [];

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entity->hasRelation($field)) {
            return $valueMap;
        }

        $relationDefs = $entityDefs->getRelation($field);

        if (
            !$relationDefs->hasForeignEntityType() ||
            !$relationDefs->hasForeignRelationName()
        ) {
            return $valueMap;
        }

        $foreignRelationType = $this->entityManager
            ->getDefs()
            ->getEntity($relationDefs->getForeignEntityType())
            ->getRelation($relationDefs->getForeignRelationName())
            ->getType();

        if ($foreignRelationType !== Entity::MANY_MANY) {
            $valueMap->{$field . 'Ids'} = [];
            $valueMap->{$field . 'Names'} = (object) [];
            $valueMap->{$field . 'Columns'} = (object) [];
        }

        return $valueMap;
    }
}
