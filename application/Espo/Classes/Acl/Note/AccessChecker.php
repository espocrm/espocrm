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

namespace Espo\Classes\Acl\Note;

use Espo\Core\Acl\Permission;
use Espo\Core\Acl\Table;
use Espo\Core\Name\Field;
use Espo\Entities\Note;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\Core\Acl\AccessEntityCREDChecker;
use Espo\Core\Acl\DefaultAccessChecker;
use Espo\Core\Acl\ScopeData;
use Espo\Core\Acl\Traits\DefaultAccessCheckerDependency;
use Espo\Core\AclManager;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;

use DateTime;
use Exception;

/**
 * @implements AccessEntityCREDChecker<Note>
 */
class AccessChecker implements AccessEntityCREDChecker
{
    use DefaultAccessCheckerDependency;

    private const EDIT_PERIOD = '7 days';
    private const DELETE_PERIOD = '1 month';

    private DefaultAccessChecker $defaultAccessChecker;
    private AclManager $aclManager;
    private EntityManager $entityManager;
    private Config $config;

    public function __construct(
        DefaultAccessChecker $defaultAccessChecker,
        AclManager $aclManager,
        EntityManager $entityManager,
        Config $config
    ) {
        $this->defaultAccessChecker = $defaultAccessChecker;
        $this->aclManager = $aclManager;
        $this->entityManager = $entityManager;
        $this->config = $config;
    }

    /**
     * @param Note $entity
     */
    public function checkEntityCreate(User $user, Entity $entity, ScopeData $data): bool
    {
        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');

        if (!$parentId || !$parentType) {
            return true;
        }

        $parent = $this->entityManager->getEntityById($parentType, $parentId);

        if ($parent && $this->aclManager->checkEntityStream($user, $parent)) {
            return true;
        }

        return false;
    }

    /**
     * @param Note $entity
     */
    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $parentId = $entity->getParentId();
        $parentType = $entity->getParentType();

        if ($parentId && $parentType) {
            $parent = $this->entityManager->getEntityById($parentType, $parentId);

            if (!$parent) {
                return false;
            }

            return $this->aclManager->checkEntityStream($user, $parent);
        }

        if ($entity->getType() !== Note::TYPE_POST) {
            return false;
        }

        if ($entity->getCreatedById() === $user->getId()) {
            return true;
        }

        if ($entity->getTargetType() === Note::TARGET_ALL) {
            return true;
        }

        if ($entity->getTargetType() === Note::TARGET_TEAMS) {
            $targetTeamIdList = $entity->getLinkMultipleIdList(Field::TEAMS);

            foreach ($user->getTeamIdList() as $teamId) {
                if (in_array($teamId, $targetTeamIdList)) {
                    return true;
                }
            }

            return false;
        }

        if ($entity->getTargetType() === Note::TARGET_USERS) {
            return in_array($user->getId(), $entity->getLinkMultipleIdList('users'));
        }

        if ($entity->getTargetType() === Note::TARGET_PORTALS) {
            return $this->aclManager->getPermissionLevel($user, Permission::PORTAL) === Table::LEVEL_YES;
        }

        return false;
    }

    /**
     * @param Note $entity
     */
    public function checkEntityEdit(User $user, Entity $entity, ScopeData $data): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$this->defaultAccessChecker->checkEntityEdit($user, $entity, $data)) {
            return false;
        }

        if (!$this->aclManager->checkOwnershipOwn($user, $entity)) {
            return false;
        }

        $createdAt = $entity->get(Field::CREATED_AT);

        if (!$createdAt) {
            return true;
        }

        $noteEditThresholdPeriod =
            '-' .  $this->config->get('noteEditThresholdPeriod', self::EDIT_PERIOD);

        $dt = new DateTime();

        $dt->modify($noteEditThresholdPeriod);

        try {
            if ($dt->format('U') > (new DateTime($createdAt))->format('U')) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param Note $entity
     */
    public function checkEntityDelete(User $user, Entity $entity, ScopeData $data): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$this->defaultAccessChecker->checkEntityDelete($user, $entity, $data)) {
            return false;
        }

        if (!$this->aclManager->checkOwnershipOwn($user, $entity)) {
            return false;
        }

        $createdAt = $entity->get(Field::CREATED_AT);

        if (!$createdAt) {
            return true;
        }

        $deleteThresholdPeriod =
            '-' . $this->config->get('noteDeleteThresholdPeriod', self::DELETE_PERIOD);

        $dt = new DateTime();

        $dt->modify($deleteThresholdPeriod);

        try {
            if ($dt->format('U') > (new DateTime($createdAt))->format('U')) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
