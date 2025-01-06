<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Repositories;

use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Modules\Crm\Entities\Reminder;
use Espo\ORM\Entity;
use Espo\Core\Di;
use Espo\Core\Utils\DateTime as DateTimeUtil;

use DateTime;
use DateTimeZone;
use RuntimeException;
use Exception;

/**
 * @extends Database<CoreEntity>
 */
class Event extends Database implements

    Di\DateTimeAware,
    Di\ConfigAware
{
    use Di\DateTimeSetter;
    use Di\ConfigSetter;

    /**
     * @param array<string, mixed> $options
     * @return void
     */
    protected function beforeSave(Entity $entity, array $options = [])
    {
        if (
            $entity->isAttributeChanged('status') &&
            in_array($entity->get('status'), $this->getNotActualStatuses())
        ) {
            $entity->set('reminders', []);
        }

        if ($entity->has('dateStartDate')) {
            $dateStartDate = $entity->get('dateStartDate');

            if (!empty($dateStartDate)) {
                $dateStart = $dateStartDate . ' 00:00:00';

                $dateStart = $this->convertDateTimeToDefaultTimezone($dateStart);

                $entity->set('dateStart', $dateStart);
            } else {
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $entity->set('dateStartDate', null);
            }
        }

        if ($entity->has('dateEndDate')) {
            $dateEndDate = $entity->get('dateEndDate');

            if (!empty($dateEndDate)) {
                try {
                    $dt = new DateTime(
                        $this->convertDateTimeToDefaultTimezone($dateEndDate . ' 00:00:00')
                    );
                } catch (Exception) {
                    throw new RuntimeException("Bad date-time.");
                }

                $dt->modify('+1 day');

                $dateEnd = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
                $entity->set('dateEnd', $dateEnd);
            } else {
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $entity->set('dateEndDate', null);
            }
        }

        parent::beforeSave($entity, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return void
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from(Reminder::ENTITY_TYPE)
            ->where([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    /**
     * @param string $string
     * @return string
     */
    protected function convertDateTimeToDefaultTimezone($string)
    {
        $timeZone = $this->config->get('timeZone') ?? 'UTC';

        try {
            $tz = new DateTimeZone($timeZone);
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $dt = DateTime::createFromFormat(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT, $string, $tz);

        if ($dt === false) {
            throw new RuntimeException("Could not parse date-time `$string`.");
        }

        $utcTz = new DateTimeZone('UTC');

        return $dt
            ->setTimezone($utcTz)
            ->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
    }

    /**
     * @return string[]
     */
    private function getNotActualStatuses(): array
    {
        return array_merge(
            $this->metadata->get("scopes.$this->entityType.completedStatusList") ?? [],
            $this->metadata->get("scopes.$this->entityType.canceledStatusList") ?? [],
        );
    }
}
