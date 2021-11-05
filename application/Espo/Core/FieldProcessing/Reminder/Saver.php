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

namespace Espo\Core\FieldProcessing\Reminder;

use Espo\ORM\Entity;

use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Core\{
    ORM\EntityManager,
    FieldProcessing\Saver as SaverInterface,
    FieldProcessing\Saver\Params,
    Utils\Util,
};

use stdClass;
use DateInterval;
use DateTime;

/**
 * @internal This class should not be removed as it's used by custom entities.
 */
class Saver implements SaverInterface
{
    protected $dateAttribute = 'dateStart';

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

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

        assert($entity instanceof CoreEntity);

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
            ->getEntity('Reminder')
            ->getField('type')
            ->getParam('options') ?? [];

        $reminderList = $entity->has('reminders') ?
            $entity->get('reminders') :
            $this->getEntityReminderDataList($entity);

        if (!$entity->isNew()) {
            $query = $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from('Reminder')
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
                $reminderId = Util::generateId();

                $query = $this->entityManager
                    ->getQueryBuilder()
                    ->insert()
                    ->into('Reminder')
                    ->columns([
                        'id',
                        'entityId',
                        'entityType',
                        'type',
                        'userId',
                        'remindAt',
                        'startAt',
                        'seconds'
                    ])
                    ->values([
                        'id' => $reminderId,
                        'entityId' => $entity->getId(),
                        'entityType' => $entityType,
                        'type' => $type,
                        'userId' => $userId,
                        'remindAt' => $remindAt->format('Y-m-d H:i:s'),
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
            ->getRDBRepository('Reminder')
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
