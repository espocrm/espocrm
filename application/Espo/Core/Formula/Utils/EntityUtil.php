<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

use Espo\Core\Formula\Exceptions\NotAllowedUsage;
use Espo\Entities\User;
use Espo\ORM\Entity;

/**
 * @internal
 * @since 9.3.0
 */
class EntityUtil
{
    /**
     * @throws NotAllowedUsage
     */
    public static function checkUpdateAccess(Entity $entity): void
    {
        if ($entity instanceof User) {
            $restrictedTypeList = self::getUserRestrictedTypeList();

            if (
                $entity->isAttributeChanged(User::ATTR_TYPE) &&
                (
                    in_array($entity->getFetched(User::ATTR_TYPE), $restrictedTypeList) ||
                    in_array($entity->getType(), $restrictedTypeList)
                )
            ) {
                throw new NotAllowedUsage("Cannot change user type.");
            }
        }
    }

    /**
     * @throws NotAllowedUsage
     */
    public static function checkRemoveAccess(Entity $entity): void
    {
        if ($entity instanceof User) {
            if (in_array($entity->getType(), self::getUserRestrictedTypeList())) {
                throw new NotAllowedUsage("Cannot remove the user.");
            }
        }
    }

    /**
     * @return string[]
     */
    private static function getUserRestrictedTypeList(): array
    {
        return [
            User::TYPE_SUPER_ADMIN,
            User::TYPE_SYSTEM,
        ];
    }
}
