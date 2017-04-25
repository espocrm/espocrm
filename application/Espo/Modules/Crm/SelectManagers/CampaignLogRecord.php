<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Modules\Crm\SelectManagers;

class CampaignLogRecord extends \Espo\Core\SelectManagers\Base
{
    protected function accessOnlyOwn(&$result)
    {
        $result['whereClause'][] = array(
            'campaign.assignedUserId' => $this->getUser()->id
        );
    }

    protected function accessOnlyTeam(&$result)
    {
        $teamIdList = $this->user->get('teamsIds');
        if (empty($teamIdList)) {
            $result['customWhere'] .= " AND campaign.assigned_user_id = ".$this->getEntityManager()->getPDO()->quote($this->getUser()->id);
            return;
        }
        $arr = [];
        if (is_array($teamIdList)) {
            foreach ($teamIdList as $teamId) {
                $arr[] = $this->getEntityManager()->getPDO()->quote($teamId);
            }
        }

        $result['customJoin'] .= " LEFT JOIN entity_team AS teamsMiddle ON teamsMiddle.entity_type = 'Campaign' AND teamsMiddle.entity_id = campaign.id AND teamsMiddle.deleted = 0";
        $result['customWhere'] .= "
            AND (
                teamsMiddle.team_id IN (" . implode(', ', $arr) . ")
                 OR
                campaign.assigned_user_id = ".$this->getEntityManager()->getPDO()->quote($this->getUser()->id)."
            )
        ";
        $result['whereClause'][] = array(
            'campaignId!=' => null
        );
    }

    protected function filterOpened(&$result)
    {
        $result['whereClause'][] = array(
            'action' => 'Opened'
        );
    }

    protected function filterSent(&$result)
    {
        $result['whereClause'][] = array(
            'action' => 'Sent'
        );
    }

    protected function filterClicked(&$result)
    {
        $result['whereClause'][] = array(
            'action' => 'Clicked'
        );
    }

    protected function filterOptedIn(&$result)
    {
        $result['whereClause'][] = array(
            'action' => 'Opted In'
        );
    }

    protected function filterOptedOut(&$result)
    {
        $result['whereClause'][] = array(
            'action' => 'Opted Out'
        );
    }

    protected function filterBounced(&$result)
    {
        $result['whereClause'][] = array(
            'action' => 'Bounced'
        );
    }

    protected function filterLeadCreated(&$result)
    {
        $result['whereClause'][] = array(
            'action' => 'Lead Created'
        );
    }
}

