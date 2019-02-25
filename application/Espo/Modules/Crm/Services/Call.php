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

namespace Espo\Modules\Crm\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class Call extends Meeting
{
    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);
        $this->loadPhoneNumbersMapField($entity);
    }

    protected function loadPhoneNumbersMapField(Entity $entity)
    {
        $map = (object) [];

        $erasedPart = 'ERASED:';

        $contactIdList = $entity->getLinkMultipleIdList('contacts');
        if (count($contactIdList)) {
            $contactList = $this->getEntityManager()->getRepository('Contact')->where([
                'id' => $contactIdList
            ])->select(['id', 'phoneNumber'])->find();
            foreach ($contactList as $contact) {
                $phoneNumber = $contact->get('phoneNumber');
                if ($phoneNumber) {
                    if (strpos($phoneNumber, $erasedPart) !== 0) {
                        $key = $contact->getEntityType() . '_' . $contact->id;
                        $map->$key = $phoneNumber;
                    }
                }
            }
        }

        $leadIdList = $entity->getLinkMultipleIdList('leads');
        if (count($leadIdList)) {
            $leadList = $this->getEntityManager()->getRepository('Lead')->where([
                'id' => $leadIdList
            ])->select(['id', 'phoneNumber'])->find();
            foreach ($leadList as $lead) {
                $phoneNumber = $lead->get('phoneNumber');
                if ($phoneNumber) {
                    if (strpos($phoneNumber, $erasedPart) !== 0) {
                        $key = $lead->getEntityType() . '_' . $lead->id;
                        $map->$key = $phoneNumber;
                    }
                }
            }
        }

        $entity->set('phoneNumbersMap', $map);
    }

    protected function afterUpdateEntity(Entity $entity, $data)
    {
        parent::afterUpdateEntity($entity, $data);
        if (isset($data->contactsIds) || isset($data->leadsIds)) {
            $this->loadPhoneNumbersMapField($entity);
        }
    }

}
