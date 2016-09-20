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
        $result['whereClause'][] = array(
            'status!=' => ['Completed', 'Canceled']
        );
    }

    protected function filterActualNotDeferred(&$result)
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
        $result['whereClause'][] = $this->convertDateTimeWhere(array(
            'type' => 'today',
            'attribute' => 'dateEnd',
            'timeZone' => $this->getUserTimeZone()
        ));
    }

    public function convertDateTimeWhere($item)
    {
        $result = parent::convertDateTimeWhere($item);

        if (empty($result)) {
            return null;
        }
        $attribute = null;
        if (!empty($item['field'])) { // for backward compatibility
            $attribute = $item['field'];
        }
        if (!empty($item['attribute'])) {
            $attribute = $item['attribute'];
        }

        if ($attribute != 'dateStart' && $attribute != 'dateEnd') {
            return $result;
        }

        $attributeDate = $attribute . 'Date';

        $dateItem = array(
            'attribute' => $attributeDate,
            'type' => $item['type']
        );
        if (!empty($item['value'])) {
            $dateItem['value'] = $item['value'];
        }

        $result = array(
            'OR' => array(
                'AND' => [
                    $result,
                    $attributeDate . '=' => null
                ],
                $this->getWherePart($dateItem)
            )
        );

        return $result;
    }
}

