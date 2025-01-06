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

namespace Espo\Modules\Crm\Tools\Activities;

use DateInterval;
use DateTime;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Reminder;
use Espo\Modules\Crm\Entities\Task;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\Tools\PopupNotification\Item;
use Espo\Tools\PopupNotification\Provider;
use Exception;

class PopupNotificationsProvider implements Provider
{
    private const REMINDER_PAST_HOURS = 24;

    private Config $config;
    private EntityManager $entityManager;

    public function __construct(
        Config $config,
        EntityManager $entityManager
    ) {
        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    /**
     * @return Item[]
     * @throws Exception
     */
    public function get(User $user): array
    {
        $userId = $user->getId();

        $dt = new DateTime();

        $pastHours = $this->config->get('reminderPastHours', self::REMINDER_PAST_HOURS);

        $now = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        $nowShifted = $dt
            ->sub(new DateInterval('PT' . $pastHours . 'H'))
            ->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        /** @var iterable<Reminder> $reminderCollection */
        $reminderCollection = $this->entityManager
            ->getRDBRepositoryByClass(Reminder::class)
            ->select([
                'id',
                'entityType',
                'entityId',
            ])
            ->where([
                'type' => Reminder::TYPE_POPUP,
                'userId' => $userId,
                'remindAt<=' => $now,
                'startAt>' => $nowShifted,
            ])
            ->find();

        $resultList = [];

        foreach ($reminderCollection as $reminder) {
            $reminderId = $reminder->getId();
            $entityType = $reminder->getTargetEntityType();
            $entityId = $reminder->getTargetEntityId();

            if (!$entityId || !$entityType) {
                continue;
            }

            $entity = $this->entityManager->getEntityById($entityType, $entityId);

            if (!$entity) {
                continue;
            }

            $data = null;

            if (
                $entity instanceof CoreEntity &&
                $entity->hasLinkMultipleField('users') &&
                $entity->hasAttribute('usersColumns')
            ) {
                $status = $entity->getLinkMultipleColumn('users', 'status', $userId);

                if ($status === Meeting::ATTENDEE_STATUS_DECLINED) {
                    $this->removeReminder($reminderId);

                    continue;
                }
            }

            $dateField = $entityType === Task::ENTITY_TYPE ?
                'dateEnd' :
                'dateStart';

            $data = (object) [
                'id' => $entity->getId(),
                'entityType' => $entityType,
                'name' => $entity->get(Field::NAME),
                'dateField' => $dateField,
                'attributes' => (object) [
                    $dateField => $entity->get($dateField),
                    $dateField . 'Date' => $entity->get($dateField . 'Date'),
                ],
            ];

            $resultList[] = new Item($reminderId, $data);
        }

        return $resultList;
    }

    private function removeReminder(string $id): void
    {
        $deleteQuery = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(Reminder::ENTITY_TYPE)
            ->where([Attribute::ID => $id])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($deleteQuery);
    }
}
