<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Modules\Crm\Business\Distribution\Lead;

class LeastBusy
{
    protected $entityManager;

    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    public function getUser($team, $targetUserPosition = null)
    {
        $params = array();
        if (!empty($targetUserPosition)) {
            $params['additionalColumnsConditions'] = array(
                'role' => $targetUserPosition
            );
        }

        $userList = $team->get('users', $params);

        if (count($userList) == 0) {
            return false;
        }

        $countHash = array();

        foreach ($userList as $user) {
            $where = array(
                'assignedUserId' => $user->id,
                'status<>' => ['Converted', 'Recycled', 'Dead']
            );
            $count = $this->getEntityManager()->getRepository('Lead')->where($where)->count();
            $countHash[$user->id] = $count;
        }

        $foundUserId = false;
        $min = false;
        foreach ($countHash as $userId => $count) {
            if ($min === false) {
                $min = $count;
                $foundUserId = $userId;
            } else {
                if ($count < $min) {
                    $min = $count;
                    $foundUserId = $userId;
                }
            }
        }

        if ($foundUserId !== false) {
            return $this->getEntityManager()->getEntity('User', $foundUserId);
        }
    }
}

