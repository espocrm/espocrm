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

namespace Espo\Core\Templates\Services;

use \Espo\ORM\Entity;

class Person extends \Espo\Services\Record
{
    protected function getDuplicateWhereClause(Entity $entity, $data)
    {
        $whereClause = [
            'OR' => []
        ];
        $toCheck = false;
        if ($entity->get('firstName') || $entity->get('lastName')) {
            $part = [];
            $part['firstName'] = $entity->get('firstName');
            $part['lastName'] = $entity->get('lastName');
            $whereClause['OR'][] = $part;
            $toCheck = true;
        }
        if (
            ($entity->get('emailAddress') || $entity->get('emailAddressData'))
            &&
            ($entity->isNew() || $entity->isAttributeChanged('emailAddress') || $entity->isAttributeChanged('emailAddressData'))
        ) {
            if ($entity->get('emailAddress')) {
                $list = [$entity->get('emailAddress')];
            }
            if ($entity->get('emailAddressData')) {
                foreach ($entity->get('emailAddressData') as $row) {
                    if (!in_array($row->emailAddress, $list)) {
                        $list[] = $row->emailAddress;
                    }
                }
            }
            foreach ($list as $emailAddress) {
                $whereClause['OR'][] = [
                    'emailAddress' => $emailAddress
                ];
                $toCheck = true;
            }
        }
        if (!$toCheck) {
            return false;
        }

        return $whereClause;
    }
}

