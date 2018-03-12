<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Modules\Crm\Repositories;

use Espo\ORM\Entity;

class Task extends \Espo\Core\Repositories\Event
{
    protected $reminderDateAttribute = 'dateEnd';

    protected $reminderSkippingStatusList = ['Completed', 'Canceled'];

    protected function init()
    {
        parent::init();
        $this->addDependency('dateTime');
        $this->addDependency('config');
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

    protected function beforeSave(Entity $entity, array $options = array())
    {
        if ($entity->isAttributeChanged('status')) {
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

        if (!$entity->isNew() && $entity->isAttributeChanged('parentId')) {
            $entity->set('accountId', null);
            $entity->set('contactId', null);
        }

        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');
        if ($parentId && $parentType) {
            $parent = $this->getEntityManager()->getEntity($parentType, $parentId);
            if ($parent) {
                $accountId = null;
                $contactId = null;
                if ($parent->getEntityType() == 'Account') {
                    $accountId = $parent->id;
                } else if ($parent->getEntityType() == 'Lead') {
                    if ($parent->get('status') == 'Converted') {
                        if ($parent->get('createdAccountId')) {
                            $accountId = $parent->get('createdAccountId');
                        }
                        if ($parent->get('createdContactId')) {
                            $contactId = $parent->get('createdContactId');
                        }
                    }
                } else if ($parent->getEntityType() == 'Contact') {
                    $contactId = $parent->id;
                }

                if (!$accountId && $parent->get('accountId') && $parent->getRelationParam('account', 'entity') == 'Account') {
                    $accountId = $parent->get('accountId');
                }
                if (!$contactId && $parent->get('contactId') && $parent->getRelationParam('contact', 'entity') == 'Contact') {
                    $contactId = $parent->get('contactId');
                }

                if ($accountId) {
                    $entity->set('accountId', $accountId);
                }
                if ($contactId) {
                    $entity->set('contactId', $contactId);
                }
            }
        }

        parent::beforeSave($entity, $options);
    }
}
