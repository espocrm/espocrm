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

namespace Espo\Core\Repositories;

use Espo\ORM\Entity;

use Espo\Core\{
    Di,
    Utils\DateTime as DateTimeUtil,
};

use DateTime;
use DateTimeZone;
use Exception;

/**
 * @extends Database<\Espo\Core\ORM\Entity>
 */
class Event extends Database implements

    Di\DateTimeAware,
    Di\ConfigAware
{
    use Di\DateTimeSetter;
    use Di\ConfigSetter;

    protected $reminderSkippingStatusList = ['Held', 'Not Held'];

    protected $preserveDuration = true;

    protected function beforeSave(Entity $entity, array $options = [])
    {
        if (
            $entity->isAttributeChanged('status') &&
            in_array($entity->get('status'), $this->reminderSkippingStatusList)
        ) {
            $entity->set('reminders', []);
        }

        if ($entity->has('dateStartDate')) {
            $dateStartDate = $entity->get('dateStartDate');

            if (!empty($dateStartDate)) {
                $dateStart = $dateStartDate . ' 00:00:00';

                $dateStart = $this->convertDateTimeToDefaultTimezone($dateStart);

                $entity->set('dateStart', $dateStart);
            }
            else {
                $entity->set('dateStartDate', null);
            }
        }

        if ($entity->has('dateEndDate')) {
            $dateEndDate = $entity->get('dateEndDate');

            if (!empty($dateEndDate)) {
                $dateEnd = $this->convertDateTimeToDefaultTimezone($dateEndDate . ' 00:00:00');

                $dt = null;

                try {
                    $dt = new DateTime($dateEnd);
                    $dt->modify('+1 day');

                    $dateEnd = $dt->format('Y-m-d H:i:s');
                }
                catch (Exception $e) {}

                $entity->set('dateEnd', $dateEnd);
            }
            else {
                $entity->set('dateEndDate', null);
            }
        }

        if (
            !$entity->isNew() &&
            $this->preserveDuration &&
            $entity->isAttributeChanged('dateStart') &&
            $entity->get('dateStart') &&
            !$entity->isAttributeChanged('dateEnd')
        ) {
            $dateEndPrevious = $entity->getFetched('dateEnd');
            $dateStartPrevious = $entity->getFetched('dateStart');

            if ($dateStartPrevious && $dateEndPrevious) {
                $dtStart = new DateTime($dateStartPrevious);
                $dtEnd = new DateTime($dateEndPrevious);
                $dt = new DateTime($entity->get('dateStart'));

                $duration = ($dtEnd->getTimestamp() - $dtStart->getTimestamp());

                $dt->modify('+' . $duration . ' seconds');

                $dateEnd = $dt->format('Y-m-d H:i:s');

                $entity->set('dateEnd', $dateEnd);
            }
        }

        parent::beforeSave($entity, $options);
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from('Reminder')
            ->where([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    protected function convertDateTimeToDefaultTimezone($string)
    {
        $timeZone = $this->config->get('timeZone') ?? 'UTC';

        $tz = new DateTimeZone($timeZone);

        $dt = null;

        try {
            $dt = DateTime::createFromFormat(
                DateTimeUtil::SYSTEM_DATE_TIME_FORMAT,
                $string,
                $tz
            );
        }
        catch (Exception $e) {}

        if (!$dt) {
            return null;
        }

        $utcTz = new DateTimeZone('UTC');

        return $dt
            ->setTimezone($utcTz)
            ->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
    }
}
