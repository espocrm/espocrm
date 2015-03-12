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

class Task extends \Espo\Core\SelectManagers\Base
{
    protected function boolFilterActual(&$result)
    {
        $result['whereClause'][] = array(
            'status!=' => array('Completed', 'Canceled')
        );
    }

    protected function boolFilterCompleted(&$result)
    {
        $result['whereClause'][] = array(
            'status' => array('Completed')
        );
    }

    protected function convertDateTimeWhere($item)
    {
        $result = parent::convertDateTimeWhere($item);

        if (empty($result)) {
            return null;
        }
        $field = $item['field'];

        if ($field != 'dateStart' && $field != 'dateEnd') {
            return $result;
        }

        $fieldDate = $field . 'Date';

        $dateItem = array(
            'field' => $fieldDate,
            'type' => $item['type']
        );
        if (!empty($item['value'])) {
            $dateItem['value'] = $item['value'];
        }

        $result = array(
            'OR' => array(
                'AND' => [
                    $result,
                    $fieldDate . '=' => null
                ],
                $this->getWherePart($dateItem)
            )
        );

        return $result;
    }
}

