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

namespace Espo\Tools\Object;

use Espo\Core\Name\Field;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Account;
use Espo\ORM\Defs;
use Espo\ORM\Type\RelationType;

/**
 * @since 10.0.0
 */
class MetadataProvider
{
    public function __construct(
        private Metadata $metadata,
        private Defs $defs,
    ) {}

    public function getAccountLink(string $entityType): ?string
    {
        $link = $this->metadata->get("scopes.$entityType.accountLink");

        if ($link) {
            return $link;
        }

        $link = Field::ACCOUNT;

        $relationDefs = $this->defs
            ->getEntity($entityType)
            ->tryGetRelation($link);

        if (!$relationDefs) {
            return null;
        }

        if (!in_array($relationDefs->getType(), [RelationType::BELONGS_TO, RelationType::HAS_ONE])) {
            return null;
        }

        if ($relationDefs->tryGetForeignEntityType() !== Account::ENTITY_TYPE) {
            return null;
        }

        return $link;
    }

    public function getParentLink(string $entityType): ?string
    {
        $link = $this->metadata->get("scopes.$entityType.parentLink");

        if ($link) {
            return $link;
        }

        $link = Field::PARENT;

        $relationDefs = $this->defs
            ->getEntity($entityType)
            ->tryGetRelation($link);

        if ($relationDefs?->getType() !== RelationType::BELONGS_TO_PARENT) {
            return null;
        }

        return $link;
    }
}
