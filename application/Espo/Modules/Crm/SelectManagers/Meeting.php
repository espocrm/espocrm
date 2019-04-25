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

namespace Espo\Modules\Crm\SelectManagers;

class Meeting extends \Espo\Core\SelectManagers\Base
{
    protected function accessOnlyOwn(&$result)
    {
        $this->setDistinct(true, $result);
        $this->addJoin(['users', 'usersAccess'], $result);
        $result['whereClause'][] = [
            'OR' => [
                'usersAccessMiddle.userId' => $this->getUser()->id,
                'assignedUserId' => $this->getUser()->id
            ]
        ];
    }

    protected function accessOnlyTeam(&$result)
    {
        $this->setDistinct(true, $result);
        $this->addLeftJoin(['teams', 'teamsAccess'], $result);
        $this->addLeftJoin(['users', 'usersAccess'], $result);

        $result['whereClause'][] = [
            'OR' => [
                'teamsAccessMiddle.teamId' => $this->getUser()->getLinkMultipleIdList('teams'),
                'usersAccessMiddle.userId' => $this->getUser()->id,
                'assignedUserId' => $this->getUser()->id
            ]
        ];
    }

    protected function boolFilterOnlyMy(&$result)
    {
        $this->addJoin(['users', 'usersFilterOnlyMy'], $result);
        $result['whereClause'][] = [
        	'usersFilterOnlyMyMiddle.userId' => $this->getUser()->id,
            'OR' => [
                'usersFilterOnlyMyMiddle.status!=' => 'Declined',
                'usersFilterOnlyMyMiddle.status=' => null
            ]
        ];
    }

    protected function filterPlanned(&$result)
    {
        $result['whereClause'][] = array(
        	'status' => 'Planned'
        );
    }

    protected function filterHeld(&$result)
    {
        $result['whereClause'][] = array(
        	'status' => 'Held'
        );
    }

    protected function filterTodays(&$result)
    {
        $result['whereClause'][] = $this->convertDateTimeWhere(array(
        	'type' => 'today',
        	'attribute' => 'dateStart',
        	'timeZone' => $this->getUserTimeZone()
        ));
    }

    public function transformDateTimeWhereItem(array $item) : ?array
    {
        $where = parent::transformDateTimeWhereItem($item);

        if (empty($where)) {
            return null;
        }
        $attribute = null;
        if (!empty($item['attribute'])) {
            $attribute = $item['attribute'];
        }

        if ($attribute != 'dateStart' && $attribute != 'dateEnd') return $where;
        if (!$this->getSeed()->hasAttribute('dateStartDate')) return $where;

        $type = $item['type'] ?? null;

        if ($type === 'isNull') return $where;
        if ($type === 'ever') return $where;
        if ($type === 'isNotNull') return $where;

        $attributeDate = $attribute . 'Date';

        $value = null;
        if (array_key_exists('value', $item)) {
            $value = $item['value'];
            if (is_string($value)) {
                if (strlen($value) > 11) {
                    return $where;
                }
            } else if (is_array($value)) {
                foreach ($value as $valueItem) {
                    if (strlen($valueItem) > 11) {
                        return $where;
                    }
                }
            }
        }

        $dateItem = [
            'attribute' => $attributeDate,
            'type' => $type,
        ];

        if (array_key_exists('value', $item)) {
            $dateItem['value'] = $value;
        }

        $where = [
            'type' => 'or',
            'value' => [
                $dateItem,
                [
                    'type' => 'and',
                    'value' => [
                        $where,
                        [
                            'type' => 'isNull',
                            'attribute' => $attributeDate
                        ]
                    ]

                ]
            ]
        ];

        return $where;
    }
}
