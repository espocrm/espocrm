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

use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Field\LinkMultipleItem;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements BeforeSave<Entity>
 */
class Collaborators implements BeforeSave
{
    public static int $order = 7;

    private const FIELD_COLLABORATORS = Field::COLLABORATORS;
    private const FIELD_ASSIGNED_USERS = Field::ASSIGNED_USERS;
    private const FIELD_ASSIGNED_USER = Field::ASSIGNED_USER;

    public function __construct(
        private Metadata $metadata,
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        if (!$this->hasCollaborators($entity)) {
            return;
        }

        if ($entity->hasLinkMultipleField(self::FIELD_ASSIGNED_USERS)) {
            $this->processAssignedUsers($entity);

            return;
        }

        $this->processAssignedUser($entity);
    }

    private function hasCollaborators(CoreEntity $entity): bool
    {
        if (!$this->metadata->get("scopes.{$entity->getEntityType()}.collaborators")) {
            return false;
        }

        if (!$entity->hasLinkMultipleField(self::FIELD_COLLABORATORS)) {
            return false;
        }

        return true;
    }

    private function processAssignedUsers(CoreEntity $entity): void
    {
        if (!$entity->has(self::FIELD_COLLABORATORS . 'Ids')) {
            return;
        }

        $assignedUsers = $entity->getValueObject(self::FIELD_ASSIGNED_USERS);
        $collaborators = $entity->getValueObject(self::FIELD_COLLABORATORS);

        if (
            !$assignedUsers instanceof LinkMultiple ||
            !$collaborators instanceof LinkMultiple
        ) {
            return;
        }

        $countBefore = $collaborators->getCount();

        foreach ($assignedUsers->getList() as $assignedUser) {
            $collaborators = $collaborators->withAdded($assignedUser);
        }

        if ($countBefore === $collaborators->getCount()) {
            return;
        }

        $entity->setValueObject(self::FIELD_COLLABORATORS, $collaborators);
    }

    private function processAssignedUser(CoreEntity $entity): void
    {
        $idAttr = self::FIELD_ASSIGNED_USER . 'Id';

        if (!$entity->hasAttribute($idAttr) || !$entity->isAttributeChanged($idAttr)) {
            return;
        }

        $assignedUser = $entity->getValueObject(self::FIELD_ASSIGNED_USER);

        if (!$assignedUser instanceof Link) {
            return;
        }

        $collaborators = $entity->getValueObject(self::FIELD_COLLABORATORS);

        if (!$collaborators instanceof LinkMultiple) {
            return;
        }

        $collaborators = $collaborators
            ->withAdded(LinkMultipleItem::create($assignedUser->getId(), $assignedUser->getName()));

        $entity->setValueObject(self::FIELD_COLLABORATORS, $collaborators);
    }
}
