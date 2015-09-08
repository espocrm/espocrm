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

namespace Espo\Modules\Crm\SelectManagers;

class MassEmail extends \Espo\Core\SelectManagers\Base
{
    protected function accessOnlyOwn(&$result)
    {
        if ($this->getAcl()->checkReadOnlyOwn('Campaign')) {
            $result['whereClause'][] = array(
                'campaign.assignedUserId' => $this->getUser()->id
            );
        } else if ($this->getAcl()->checkReadOnlyTeam('Campaign')) {
            $teamIdList = $this->user->get('teamsIds');
            if (empty($teamIdList)) {
                $result['customWhere'] .= " AND 0 ";
            }
            $arr = [];
            if (is_array($teamIdList)) {
                foreach ($teamIdList as $teamId) {
                    $arr[] = $this->getEntityManager()->getPDO()->quote($teamId);
                }
            }

            $result['customJoin'] .= " JOIN teamsMiddle AS teamsMiddle ON teamsMiddle.entity_type = 'Campaign' AND teamsMiddle.entity_id = campaign.id AND teamsMiddle.deleted = 0";
            $result['customWhere'] .= " AND teamsMiddle.team_id IN (" . implode(', ', $arr) . ") ";
            $result['whereClause'][] = array(
                'campaignId!=' => null
            );
        }
    }
}

