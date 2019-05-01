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

class Task extends \Espo\Core\SelectManagers\Base
{
    protected $selectAttributesDependancyMap = [
        'dateEnd' => ['status']
    ];

    protected function boolFilterActual(&$result)
    {
        $this->filterActual($result);
    }

    protected function boolFilterCompleted(&$result)
    {
        $this->filterCompleted($result);
    }

    protected function filterActual(&$result)
    {
        $result['whereClause'][] = [
            'status!=' => ['Completed', 'Canceled', 'Deferred']
        ];
    }

    protected function filterDeferred(&$result)
    {
        $result['whereClause'][] = array(
            'status=' => 'Deferred'
        );
    }

    protected function filterActualStartingNotInFuture(&$result)
    {
        $result['whereClause'][] = array(
            array(
                'status!=' => ['Completed', 'Canceled', 'Deferred']
            ),
            array(
                'OR' => array(
                    array(
                        'dateStart' => null
                    ),
                    array(
                        'dateStart!=' => null,
                        'OR' => array(
                            $this->convertDateTimeWhere(array(
                                'type' => 'past',
                                'attribute' => 'dateStart',
                                'timeZone' => $this->getUserTimeZone()
                            )),
                            $this->convertDateTimeWhere(array(
                                'type' => 'today',
                                'attribute' => 'dateStart',
                                'timeZone' => $this->getUserTimeZone()
                            ))
                        )
                    )
                )
            )
        );
    }

    protected function filterCompleted(&$result)
    {
        $result['whereClause'][] = array(
            'status' => ['Completed']
        );
    }

    protected function filterOverdue(&$result)
    {
        $result['whereClause'][] = [
            $this->convertDateTimeWhere(array(
                'type' => 'past',
                'attribute' => 'dateEnd',
                'timeZone' => $this->getUserTimeZone()
            )),
            [
                array(
                    'status!=' => ['Completed', 'Canceled']
                )
            ]
        ];
    }

    protected function filterTodays(&$result)
    {
        $result['whereClause'][] = $this->convertDateTimeWhere([
            'type' => 'today',
            'attribute' => 'dateEnd',
            'timeZone' => $this->getUserTimeZone()
        ]);
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
