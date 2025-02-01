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

namespace Espo\Hooks\Common;

use Espo\Core\Hook\Hook\BeforeRemove;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Repository\Option\RemoveOptions;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Core\Hook\Hook\BeforeSave;

/**
 * Handles 'deleteId' on soft-deletes.
 *
 * @implements BeforeSave<Entity>
 * @implements BeforeRemove<Entity>
 */
class DeleteId implements BeforeSave, BeforeRemove
{
    private const ID_ATTR = 'deleteId';
    private const DELETED_ATTR = Attribute::DELETED;

    public function __construct(
        private Metadata $metadata,
    ) {}

    public function beforeRemove(Entity $entity, RemoveOptions $options): void
    {
        if (!$this->hasDeleteId($entity)) {
            return;
        }

        $entity->set(self::ID_ATTR, Util::generateId());
    }

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (!$this->hasDeleteId($entity)) {
            return;
        }

        if (!$entity->isAttributeChanged(self::DELETED_ATTR)) {
            return;
        }

        $deleteId = $entity->get(self::DELETED_ATTR) ? Util::generateId() : '0';

        $entity->set(self::ID_ATTR, $deleteId);
    }

    private function hasDeleteId(Entity $entity): bool
    {
        return $entity->hasAttribute(self::DELETED_ATTR) &&
            $this->metadata->get("entityDefs.{$entity->getEntityType()}.deleteId");
    }
}
