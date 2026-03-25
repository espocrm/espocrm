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

namespace Espo\Core\Formula\Utils;

use Espo\Core\Acl\SystemRestriction;
use Espo\Core\Acl\Exceptions\Restricted;
use Espo\Core\Formula\Exceptions\NotAllowedUsage;
use Espo\ORM\Entity;

/**
 * @internal
 * @since 9.3.0
 */
class EntityUtil
{
    public function __construct(
        private SystemRestriction $systemRestriction,
    ) {}

    /**
     * @throws NotAllowedUsage
     */
    public function assertUpdateAccess(Entity $entity): void
    {
        try {
            $this->systemRestriction->assertUpdate($entity);
        } catch (Restricted $e) {
            throw new NotAllowedUsage($e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws NotAllowedUsage
     */
    public function assertRemoveAccess(Entity $entity): void
    {
        try {
            $this->systemRestriction->assertRemoval($entity);
        } catch (Restricted $e) {
            throw new NotAllowedUsage($e->getMessage(), previous: $e);
        }
    }

    /**
     * @return string[]
     */
    public function getWriteRestrictedAttributeList(string $entityType): array
    {
        return $this->systemRestriction->getWriteRestrictedAttributeList($entityType);
    }
}
