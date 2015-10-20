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

namespace Espo\Modules\Crm\Repositories;

use Espo\ORM\Entity;

class Task extends \Espo\Core\ORM\Repositories\RDB
{
    protected function init()
    {
        $this->dependencies[] = 'dateTime';
        $this->dependencies[] = 'config';
    }

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    protected function getDateTime()
    {
        return $this->getInjection('dateTime');
    }

    protected function convertDateTimeToDefaultTimezone($string)
    {
        $dateTime = \DateTime::createFromFormat($this->getDateTime()->getInternalDateTimeFormat(), $string);
        $timeZone = $this->getConfig()->get('timeZone');
        if (empty($timeZone)) {
            $timeZone = 'UTC';
        }
        $tz = $timezone = new \DateTimeZone($timeZone);

        if ($dateTime) {
            return $dateTime->setTimezone($tz)->format($this->getDateTime()->getInternalDateTimeFormat());
        }
        return null;
    }

    protected function beforeSave(Entity $entity, array $options)
    {
        parent::beforeSave($entity, $options);

        if ($entity->isFieldChanged('status')) {
            if ($entity->get('status') == 'Completed') {
                $entity->set('dateCompleted', date('Y-m-d H:i:s'));
            } else {
                $entity->set('dateCompleted', null);
            }
        }

        if ($entity->has('dateStartDate')) {
            $dateStartDate = $entity->get('dateStartDate');
            if (!empty($dateStartDate)) {
                $dateStart = $dateStartDate . ' 00:00:00';
                $dateStart = $this->convertDateTimeToDefaultTimezone($dateStart);

                $entity->set('dateStart', $dateStart);
            } else {
                $entity->set('dateStartDate', null);
            }
        }

        if ($entity->has('dateEndDate')) {
            $dateEndDate = $entity->get('dateEndDate');
            if (!empty($dateEndDate)) {
                $dateEnd = $dateEndDate . ' 00:00:00';
                $dateEnd = $this->convertDateTimeToDefaultTimezone($dateEnd);

                $entity->set('dateEnd', $dateEnd);
            } else {
                $entity->set('dateEndDate', null);
            }
        }

        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');
        if (!empty($parentId) || !empty($parentType)) {
            $parent = $this->getEntityManager()->getEntity($parentType, $parentId);
            if (!empty($parent)) {
                $accountId = null;
                if ($parent->getEntityType() == 'Account') {
                    $accountId = $parent->id;
                } else if ($parent->get('accountId')) {
                    $accountId = $parent->get('accountId');
                } else if ($parent->getEntityType() == 'Lead') {
                    if ($parent->get('status') == 'Converted') {
                        if ($parent->get('createdAccountId')) {
                            $accountId = $parent->get('createdAccountId');
                        }
                    }
                }
                if (!empty($accountId)) {
                    $entity->set('accountId', $accountId);
                }
            }
        }
    }

}

