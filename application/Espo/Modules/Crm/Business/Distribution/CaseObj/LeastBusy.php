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

use Espo\ORM\EntityManager;
use Espo\Core\Utils\Metadata;

use Espo\Entities\User;
use Espo\Entities\Team;

class LeastBusy
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Metadata
     */
    protected $metadata;

    public function __construct(EntityManager $entityManager, Metadata $metadata)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
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

        $countHash = [];

        $notActualStatusList =
            $this->metadata->get(['entityDefs', 'Case', 'fields', 'status', 'notActualOptions']) ?? [];

        foreach ($userList as $user) {
            $count = $this->entityManager
                ->getRDBRepository('Case')
                ->where([
                    'assignedUserId' => $user->getId(),
                    'status!=' => $notActualStatusList,
                ])
                ->count();

            $countHash[$user->getId()] = $count;
        }

        $foundUserId = false;
        $min = false;

        foreach ($countHash as $userId => $count) {
            if ($min === false) {
                $min = $count;
                $foundUserId = $userId;
            }
            else {
                if ($count < $min) {
                    $min = $count;
                    $foundUserId = $userId;
                }
            }
        }

        if ($foundUserId !== false) {
            return $this->entityManager->getEntity('User', $foundUserId);
        }

        return null;
    }
}
