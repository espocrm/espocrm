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

namespace Espo\Core\Acl;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\ORM\Defs;

class OwnerUserFieldProvider
{
    protected const FIELD_ASSIGNED_USERS = Field::ASSIGNED_USERS;
    protected const FIELD_ASSIGNED_USER = Field::ASSIGNED_USER;
    protected const FIELD_CREATED_BY = Field::CREATED_BY;

    public function __construct(private Defs $ormDefs, private Metadata $metadata)
    {}

    /**
     * Get an entity field that stores an owner-user (or multiple users).
     * Must be a link or linkMultiple field. NULL means no owner.
     */
    public function get(string $entityType): ?string
    {
        $value = $this->metadata->get(['aclDefs', $entityType, 'readOwnerUserField']);

        if ($value) {
            return $value;
        }

        $defs = $this->ormDefs->getEntity($entityType);

        if (
            $defs->hasField(self::FIELD_ASSIGNED_USERS) &&
            $defs->getField(self::FIELD_ASSIGNED_USERS)->getType() === FieldType::LINK_MULTIPLE &&
            $defs->hasRelation(self::FIELD_ASSIGNED_USERS) &&
            $defs->getRelation(self::FIELD_ASSIGNED_USERS)->getForeignEntityType() === User::ENTITY_TYPE
        ) {
            return self::FIELD_ASSIGNED_USERS;
        }

        if (
            $defs->hasField(self::FIELD_ASSIGNED_USER) &&
            $defs->getField(self::FIELD_ASSIGNED_USER)->getType() === FieldType::LINK &&
            $defs->hasRelation(self::FIELD_ASSIGNED_USER) &&
            $defs->getRelation(self::FIELD_ASSIGNED_USER)->getForeignEntityType() === User::ENTITY_TYPE
        ) {
            return self::FIELD_ASSIGNED_USER;
        }

        if (
            $defs->hasField(self::FIELD_CREATED_BY) &&
            $defs->getField(self::FIELD_CREATED_BY)->getType() === FieldType::LINK &&
            $defs->hasRelation(self::FIELD_CREATED_BY) &&
            $defs->getRelation(self::FIELD_CREATED_BY)->getForeignEntityType() === User::ENTITY_TYPE
        ) {
            return self::FIELD_CREATED_BY;
        }

        return null;
    }
}
