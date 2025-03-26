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

namespace Espo\Modules\Crm\Tools\Case\Distribution;

use Espo\Entities\User;
use Espo\Entities\Team;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\ORM\EntityManager;

class RoundRobin
{
    public function __construct(private EntityManager $entityManager)
    {}

    public function getUser(Team $team, ?string $targetUserPosition = null): ?User
    {
        $where = [
            'isActive' => true,
        ];

        if (!empty($targetUserPosition)) {
            $where['@relation.role'] = $targetUserPosition;
        }

        $userList = $this->entityManager
            ->getRDBRepositoryByClass(Team::class)
            ->getRelation($team, 'users')
            ->where($where)
            ->order('id')
            ->find();

        if (count($userList) === 0) {
            return null;
        }

        $userIdList = [];

        foreach ($userList as $user) {
            $userIdList[] = $user->getId();
        }

        /** @var ?CaseObj $case */
        $case = $this->entityManager
            ->getRDBRepository(CaseObj::ENTITY_TYPE)
            ->where([
                'assignedUserId' => $userIdList,
            ])
            ->order('number', 'DESC')
            ->findOne();

        if (empty($case)) {
            $num = 0;
        } else {
            $num = array_search($case->getAssignedUser()?->getId(), $userIdList);

            if ($num === false || $num == count($userIdList) - 1) {
                $num = 0;
            } else {
                $num++;
            }
        }

        $id = $userIdList[$num];

        /** @var User */
        return $this->entityManager->getEntityById(User::ENTITY_TYPE, $id);
    }
}
