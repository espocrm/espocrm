<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Record\Defaults;

use Espo\Core\Acl;
use Espo\Core\Acl\Permission;
use Espo\Core\Acl\Table as AclTable;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\FieldUtil;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use RuntimeException;

/**
 * @implements Populator<Entity>
 */
class DefaultPopulator implements Populator
{
    public function __construct(
        private Acl $acl,
        private User $user,
        private FieldUtil $fieldUtil,
        private Config $config,
        private EntityManager $entityManager
    ) {}

    public function populate(Entity $entity): void
    {
        $entityType = $entity->getEntityType();

        if ($this->isAssignedUserShouldBeSetWithSelf($entityType)) {
            $entity->set('assignedUserId', $this->user->getId());
            $entity->set('assignedUserName', $this->user->getName());
        }

        if ($this->toAddDetailTeam($entity)) {
            $defaultTeamId = $this->user->getDefaultTeam()?->getId();

            if (!$defaultTeamId || !$entity instanceof CoreEntity) {
                throw new RuntimeException();
            }

            $entity->addLinkMultipleId('teams', $defaultTeamId);

            $teamsNames = $entity->get('teamsNames');

            if (!$teamsNames || !is_object($teamsNames)) {
                $teamsNames = (object) [];
            }

            $teamsNames->$defaultTeamId = $this->user->get('defaultTeamName');

            $entity->set('teamsNames', $teamsNames);
        }

        foreach ($this->fieldUtil->getEntityTypeFieldList($entityType) as $field) {
            $type = $this->fieldUtil->getEntityTypeFieldParam($entityType, $field, 'type');

            if (
                $type === FieldType::CURRENCY &&
                $entity->get($field) &&
                !$entity->get($field . 'Currency')
            ) {
                $entity->set($field . 'Currency', $this->config->get('defaultCurrency'));
            }
        }
    }

    /**
     * If no edit access to assignedUser field.
     */
    private function isAssignedUserShouldBeSetWithSelf(string $entityType): bool
    {
        if ($this->user->isPortal()) {
            return false;
        }

        $defs = $this->entityManager->getDefs()->getEntity($entityType);

        if ($defs->tryGetField('assignedUser')?->getType() !== FieldType::LINK) {
            return false;
        }

        if (
            $this->acl->getPermissionLevel(Permission::ASSIGNMENT) === AclTable::LEVEL_NO &&
            !$this->user->isApi()
        ) {
            return true;
        }

        if (!$this->acl->checkField($entityType, 'assignedUser', AclTable::ACTION_EDIT)) {
            return true;
        }

        return false;
    }

    /**
     * @phpstan-assert-if-true CoreEntity $entity
     */
    private function toAddDetailTeam(Entity $entity): bool
    {
        if ($this->user->isPortal()) {
            return false;
        }

        if (!$this->user->getDefaultTeam()) {
            return false;
        }

        if (!$entity instanceof CoreEntity) {
            return false;
        }

        $entityType = $entity->getEntityType();

        $defs = $this->entityManager->getDefs()->getEntity($entityType);

        if ($defs->tryGetField('teams')?->getType() !== FieldType::LINK_MULTIPLE) {
            return false;
        }

        if ($entity->hasLinkMultipleId('teams', $this->user->getDefaultTeam()->getId())) {
            return false;
        }

        if ($this->acl->getPermissionLevel(Permission::ASSIGNMENT) === AclTable::LEVEL_NO) {
            return true;
        }

        if (!$this->acl->checkField($entityType, 'teams', AclTable::ACTION_EDIT)) {
            return true;
        }

        return false;
    }
}
