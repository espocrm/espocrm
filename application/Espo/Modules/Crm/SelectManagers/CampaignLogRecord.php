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

namespace Espo\Modules\Crm\SelectManagers;

class CampaignLogRecord extends \Espo\Core\Select\SelectManager
{
    protected function accessOnlyOwn(&$result)
    {
        $this->addLeftJoin(['campaign', 'campaignAccess'], $result);

        $result['whereClause'][] = [
            'campaignAccess.assignedUserId' => $this->getUser()->id,
        ];
    }

    protected function accessOnlyTeam(&$result)
    {
        $this->addLeftJoin(['campaign', 'campaignAccess'], $result);

        $teamIdList = $this->user->getLinkMultipleIdList('teams');

        if (empty($teamIdList)) {
            $result['whereClause'][] = [
                'campaignAccess.assignedUserId' => $this->getUser()->id,
            ];

            return;
        }

        $this->addLeftJoin(
            [
                'EntityTeam',
                'entityTeamAccess',
                [
                    'entityTeamAccess.entityType' => 'Campaign',
                    'entityTeamAccess.entityId:' => 'campaignAccess.id',
                    'entityTeamAccess.deleted' => false,
                ]
            ],
            $result
        );

        $result['whereClause'][] = [
            'OR' => [
                'entityTeamAccess.teamId' => $teamIdList,
                'campaignAccess.assignedUserId' => $this->getUser()->id,
            ],
            'campaignId!=' => null,
        ];
    }

    protected function filterOpened(&$result)
    {
        $result['whereClause'][] = [
            'action' => 'Opened'
        ];
    }

    protected function filterSent(&$result)
    {
        $result['whereClause'][] = [
            'action' => 'Sent'
        ];
    }

    protected function filterClicked(&$result)
    {
        $result['whereClause'][] = [
            'action' => 'Clicked'
        ];
    }

    protected function filterOptedOut(&$result)
    {
        $result['whereClause'][] = [
            'action' => 'Opted Out'
        ];
    }

    protected function filterOptedIn(&$result)
    {
        $result['whereClause'][] = [
            'action' => 'Opted In'
        ];
    }

    protected function filterBounced(&$result)
    {
        $result['whereClause'][] = [
            'action' => 'Bounced'
        ];
    }

    protected function filterLeadCreated(&$result)
    {
        $result['whereClause'][] = [
            'action' => 'Lead Created'
        ];
    }
}
