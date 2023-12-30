<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\FieldProcessing\Reminder;

use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Id\RecordIdGenerator;
use Espo\Modules\Crm\Entities\Reminder;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\FieldProcessing\Saver as SaverInterface;
use Espo\Core\FieldProcessing\Saver\Params;
use Espo\Core\ORM\EntityManager;

use stdClass;
use DateInterval;
use DateTime;

/**
 * @internal This class should not be removed as it's used by custom entities.
 *
 * @implements SaverInterface<CoreEntity>
 */
class Saver implements SaverInterface
{
    protected string $dateAttribute = 'dateStart';

    public function __construct(
        private EntityManager $entityManager,
        private RecordIdGenerator $idGenerator
    ) {}

    /**
     * @param CoreEntity $entity
     */
    public function process(Entity $entity, Params $params): void
    {
        $entityType = $entity->getEntityType();

        $hasReminder = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->hasField('reminders');

        if (!$hasReminder) {
            return;
        }

        $dateAttribute = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->getField('reminders')
            ->getParam('dateField') ??
            $this->dateAttribute;

        $toProcess =
            $entity->isNew() ||
            $entity->isAttributeChanged('assignedUserId') ||
            ($entity->hasLinkMultipleField('assignedUsers') && $entity->isAttributeChanged('assignedUsersIds')) ||
            ($entity->hasLinkMultipleField('users') && $entity->isAttributeChanged('usersIds')) ||
            $entity->isAttributeChanged($dateAttribute) ||
            $entity->has('reminders');

        if (!$toProcess) {
            return;
        }

        $reminderTypeList = $this->entityManager
            ->getDefs()
            ->getEntity(Reminder::ENTITY_TYPE)
            ->getField('type')
            ->getParam('options') ?? [];

        $reminderList = $entity->has('reminders') ?
            $entity->get('reminders') :
            $this->getEntityReminderDataList($entity);

        if (!$entity->isNew()) {
            $query = $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from(Reminder::ENTITY_TYPE)
                ->where([
                    'entityId' => $entity->getId(),
                    'entityType' => $entityType,
                    'deleted' => false,
                ])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($query);
        }

        if (empty($reminderList)) {
            return;
        }

        $dateValue = $entity->get($dateAttribute);

        if (!$entity->has($dateAttribute)) {
            $reloadedEntity = $this->entityManager->getEntity($entityType, $entity->getId());

            if ($reloadedEntity) {
                $dateValue = $reloadedEntity->get($dateAttribute);
            }
        }

        if (!$dateValue) {
            return;
        }

        if ($entity->hasLinkMultipleField('users')) {
            $userIdList = $entity->getLinkMultipleIdList('users');
        }
        else if ($entity->hasLinkMultipleField('assignedUsers')) {
            $userIdList = $entity->getLinkMultipleIdList('assignedUsers');
        }
        else {
            $userIdList = [];

            if ($entity->get('assignedUserId')) {
                $userIdList[] = $entity->get('assignedUserId');
            }
        }

        if (empty($userIdList)) {
            return;
        }

        $dateValueObj = new DateTime($dateValue);

        foreach ($reminderList as $item) {
            $remindAt = clone $dateValueObj;
            $seconds = intval($item->seconds);
            $type = $item->type;

            if (!in_array($type , $reminderTypeList)) {
                continue;
            }

            $remindAt->sub(new DateInterval('PT' . $seconds . 'S'));

            foreach ($userIdList as $userId) {
                $reminderId = $this->idGenerator->generate();

                $query = $this->entityManager
                    ->getQueryBuilder()
                    ->insert()
                    ->into(Reminder::ENTITY_TYPE)
                    ->columns([
                        'id',
                        'entityId',
                        'entityType',
                        'type',
                        'userId',
                        'remindAt',
                        'startAt',
                        'seconds',
                    ])
                    ->values([
                        'id' => $reminderId,
                        'entityId' => $entity->getId(),
                        'entityType' => $entityType,
                        'type' => $type,
                        'userId' => $userId,
                        'remindAt' => $remindAt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
                        'startAt' => $dateValue,
                        'seconds' => $seconds,
                    ])
                    ->build();

                $this->entityManager->getQueryExecutor()->execute($query);
            }
        }
    }

    /**
     * @return stdClass[]
     */
    private function getEntityReminderDataList(Entity $entity): array
    {
        $reminderDataList = [];

        $reminderCollection = $this->entityManager
            ->getRDBRepository(Reminder::ENTITY_TYPE)
            ->select(['seconds', 'type'])
            ->where([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
            ])
            ->distinct()
            ->order('seconds')
            ->find();

        foreach ($reminderCollection as $reminder) {
            $reminderDataList[] = (object) [
                'seconds' => $reminder->get('seconds'),
                'type' => $reminder->get('type'),
            ];
        }

        return $reminderDataList;
    }
}
