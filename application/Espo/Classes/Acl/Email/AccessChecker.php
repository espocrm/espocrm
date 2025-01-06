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

namespace Espo\Classes\Acl\Email;

use Espo\Core\Name\Field;
use Espo\Entities\User;
use Espo\Entities\Email;
use Espo\ORM\Entity;
use Espo\Core\Acl\AccessEntityCREDSChecker;
use Espo\Core\Acl\DefaultAccessChecker;
use Espo\Core\Acl\ScopeData;
use Espo\Core\Acl\Table;
use Espo\Core\Acl\Traits\DefaultAccessCheckerDependency;

/**
 * @implements AccessEntityCREDSChecker<Email>
 */
class AccessChecker implements AccessEntityCREDSChecker
{
    use DefaultAccessCheckerDependency;

    public function __construct(DefaultAccessChecker $defaultAccessChecker)
    {
        $this->defaultAccessChecker = $defaultAccessChecker;
    }

    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        /** @var Email $entity */

        if ($this->defaultAccessChecker->checkEntityRead($user, $entity, $data)) {
            return true;
        }

        if ($data->isFalse()) {
            return false;
        }

        if ($data->getRead() === Table::LEVEL_NO) {
            return false;
        }

        if (!$entity->has('usersIds')) {
            $entity->loadLinkMultipleField('users');
        }

        $userIdList = $entity->get('usersIds');

        if (is_array($userIdList) && in_array($user->getId(), $userIdList)) {
            return true;
        }

        return false;
    }

    public function checkEntityDelete(User $user, Entity $entity, ScopeData $data): bool
    {
        /** @var Email $entity */

        if ($user->isAdmin()) {
            return true;
        }

        if ($data->isFalse()) {
            return false;
        }

        if ($data->getDelete() === Table::LEVEL_OWN) {
            if ($user->getId() === $entity->get('assignedUserId')) {
                return true;
            }

            if ($user->getId() === $entity->get('createdById')) {
                return true;
            }

            $assignedUserIdList = $entity->getLinkMultipleIdList(Field::ASSIGNED_USERS);

            if (
                count($assignedUserIdList) === 1 &&
                $entity->hasLinkMultipleId(Field::ASSIGNED_USERS, $user->getId())
            ) {
                return true;
            }

            return false;
        }

        if ($this->defaultAccessChecker->checkEntityDelete($user, $entity, $data)) {
            return true;
        }

        if ($data->getEdit() === Table::LEVEL_NO && $data->getCreate() === Table::LEVEL_NO) {
            return false;
        }

        if ($entity->get('createdById') !== $user->getId()) {
            return false;
        }

        if (
            $entity->getStatus() !== Email::STATUS_SENT &&
            $entity->getStatus() !== Email::STATUS_ARCHIVED
        ) {
            return true;
        }

        return false;
    }

    public function checkEntityEdit(User $user, Entity $entity, ScopeData $data): bool
    {
        /** @var Email $entity */

        if (
            $entity->getStatus() === Email::STATUS_DRAFT &&
            $entity->getCreatedBy() &&
            $entity->getCreatedBy()->getId() === $user->getId()
        ) {
            return true;
        }

        return $this->defaultAccessChecker->checkEntityEdit($user, $entity, $data);
    }

    public function checkEdit(User $user, ScopeData $data): bool
    {
        if ($data->getCreate() === Table::LEVEL_YES) {
            return true;
        }

        return $this->defaultAccessChecker->checkEdit($user, $data);
    }

    public function checkDelete(User $user, ScopeData $data): bool
    {
        if ($data->getCreate() === Table::LEVEL_YES) {
            return true;
        }

        return $this->defaultAccessChecker->checkDelete($user, $data);
    }
}
