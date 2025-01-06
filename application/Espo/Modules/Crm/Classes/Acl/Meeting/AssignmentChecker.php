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

namespace Espo\Modules\Crm\Classes\Acl\Meeting;

use Espo\Core\Acl;
use Espo\Core\Acl\AssignmentChecker as AssignmentCheckerInterface;
use Espo\Core\Acl\DefaultAssignmentChecker;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;

/**
 * @implements AssignmentCheckerInterface<Meeting|Call>
 */
class AssignmentChecker implements AssignmentCheckerInterface
{
    public function __construct(
        private DefaultAssignmentChecker $defaultAssignmentChecker,
        private EntityManager $entityManager,
        private Acl $acl
    ) {}

    public function check(User $user, Entity $entity): bool
    {
        if (!$this->defaultAssignmentChecker->check($user, $entity)) {
            return false;
        }

        $userIds = $this->getUserIds($entity);

        foreach ($userIds as $userId) {
            if (!$this->acl->checkAssignmentPermission($userId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    private function getUserIds(Meeting|Call $entity): array
    {
        $userIdList = $entity->getUsers()->getIdList();

        if ($entity->isNew()) {
            return $userIdList;
        }

        $newIdList = [];
        $existingIdList = [];

        $usersCollection = $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'users')
            ->select(Attribute::ID)
            ->find();

        foreach ($usersCollection as $user) {
            $existingIdList[] = $user->getId();
        }

        foreach ($userIdList as $id) {
            if (!in_array($id, $existingIdList)) {
                $newIdList[] = $id;
            }
        }

        return $newIdList;
    }
}
