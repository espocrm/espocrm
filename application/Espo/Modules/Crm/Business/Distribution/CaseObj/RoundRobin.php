<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Modules\Crm\Business\Distribution\CaseObj;

use Espo\Entities\User;
use Espo\Entities\Team;

use Espo\ORM\EntityManager;

class RoundRobin
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Team $team
     * @param ?string $targetUserPosition
     * @return User|null
     */
    public function getUser($team, $targetUserPosition = null)
    {
        $selectParams = [
            'whereClause' => ['isActive' => true],
            'orderBy' => 'id',
        ];

        if (!empty($targetUserPosition)) {
            $selectParams['additionalColumnsConditions'] = ['role' => $targetUserPosition];
        }

        $userList = $team->get('users', $selectParams);

        if (count($userList) == 0) {
            return null;
        }

        $userIdList = [];

        foreach ($userList as $user) {
            $userIdList[] = $user->id;
        }

        $case = $this->entityManager
            ->getRDBRepository('Case')
            ->where([
                'assignedUserId' => $userIdList,
            ])
            ->order('number', 'DESC')
            ->findOne();

        if (empty($case)) {
            $num = 0;
        }
        else {
            $num = array_search($case->get('assignedUserId'), $userIdList);

            if ($num === false || $num == count($userIdList) - 1) {
                $num = 0;
            }
            else {
                $num++;
            }
        }

        $id = $userIdList[$num];

        return $this->entityManager->getEntity('User', $id);
    }
}
