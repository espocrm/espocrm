<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

namespace Espo\SelectManagers;

class User extends \Espo\Core\SelectManagers\Base
{
    protected function access(&$result)
    {
        parent::access($result);

        if (!$this->getUser()->isAdmin()) {
            $result['whereClause'][] = array(
                'isActive' => true
            );
        }
        $result['whereClause'][] = array(
            'isSuperAdmin' => false
        );
    }

    protected function filterActive(&$result)
    {
        $result['whereClause'][] = array(
            'isActive' => true
        );
    }

    protected function boolFilterOnlyMyTeam(&$result)
    {
        $teamIds = $this->getUser()->get('teamsIds');
        if (empty($teamIds)) {
            $teamIds = [];
        }

        if (!in_array('teams', $result['joins'])) {
        	$result['joins'][] = 'teams';
        }
        $result['whereClause'][] = array(
        	'teamsMiddle.teamId' => $teamIds
        );
        $result['distinct'] = true;
    }
}

