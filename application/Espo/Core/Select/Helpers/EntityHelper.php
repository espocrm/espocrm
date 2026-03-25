<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Select\Helpers;

use Espo\ORM\BaseEntity;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;

/**
 * @internal
 */
class EntityHelper
{
    public function __construct(
        private Defs $defs,
    ) {}

    /**
     * @internal
     */
    public function getRelationEntityType(Entity $entity, string $relation): ?string
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getRelationParam($relation, RelationParam::ENTITY);
        }

        $entityDefs = $this->defs->getEntity($entity->getEntityType());

        if (!$entityDefs->hasRelation($relation)) {
            return null;
        }

        return $entityDefs
            ->getRelation($relation)
            ->tryGetForeignEntityType();
    }
}
