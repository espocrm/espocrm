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

namespace Espo\Core\FieldProcessing\Reminder;

use Espo\Core\Field\DateTime;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Id\RecordIdGenerator;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Preferences;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Reminder;
use Espo\Modules\Crm\Entities\Task;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\FieldProcessing\Saver as SaverInterface;
use Espo\Core\FieldProcessing\Saver\Params;
use Espo\Core\ORM\EntityManager;

use stdClass;

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
        private RecordIdGenerator $idGenerator,
        private User $user,
        private Metadata $metadata
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        $entityType = $entity->getEntityType();

        if (!$this->hasRemindersField($entityType)) {
            return;
        }

        $dateAttribute = $this->getDateAttribute($entityType);

        if ($this->toRemove($entity)) {
            $this->deleteAll($entity);

            return;
        }

        if (!$this->toProcess($entity, $dateAttribute)) {
            return;
        }

        $typeList = $this->getTypeList();

        $onlyRemindersFieldChanged = $this->onlyRemindersFieldChanged($entity, $dateAttribute);

        if (!$entity->isNew() && !$onlyRemindersFieldChanged) {
            $this->deleteAll($entity);
        }

        if (!$entity->isNew() && $onlyRemindersFieldChanged) {
            $this->deleteAllForUser($entity);
        }

        $startString = $this->getStartString($entity, $dateAttribute);

        if (!$startString) {
            return;
        }

        $userIdList = $this->getUserIdList($entity);

        if ($userIdList === []) {
            return;
        }

        if ($onlyRemindersFieldChanged && in_array($this->user->getId(), $userIdList)) {
            $userIdList = [$this->user->getId()];
        }

        $start = DateTime::fromString($startString);

        foreach ($userIdList as $userId) {
            $usePreferences = $userId !== $this->user->getId() ||
                !$entity->has('reminders') && $entity->isNew();

            $reminderList = $usePreferences ?
                $this->getPreferencesReminderList($typeList, $userId, $entityType) :
                $this->getReminderList($entity, $typeList);

            foreach ($reminderList as $item) {
                $this->createReminder($entity, $userId, $start, $item);
            }
        }
    }

    /**
     * @return object{seconds: int, type: string}[]
     */
    private function getEntityReminderDataList(CoreEntity $entity): array
    {
        $dataList = [];

        /** @var iterable<Reminder> $collection */
        $collection = $this->entityManager
            ->getRDBRepository(Reminder::ENTITY_TYPE)
            ->select(['seconds', 'type'])
            ->where([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
                'userId' => $this->user->getId(),
            ])
            ->distinct()
            ->order('seconds')
            ->find();

        foreach ($collection as $reminder) {
            $dataList[] = (object) [
                'seconds' => $reminder->getSeconds(),
                'type' => $reminder->getType(),
            ];
        }

        return $dataList;
    }

    private function getDateAttribute(string $entityType): string
    {
        return $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->getField('reminders')
            ->getParam('dateField') ??
            $this->dateAttribute;
    }

    private function hasRemindersField(string $entityType): bool
    {
        return $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->hasField('reminders');
    }

    private function isNewOrChanged(CoreEntity $entity, string $dateAttribute): bool
    {
        return $entity->isNew() ||
            $this->toReCreate($entity) ||
            $entity->isAttributeChanged('assignedUserId') ||
            (
                $entity->hasLinkMultipleField(Field::ASSIGNED_USERS) &&
                $entity->isAttributeChanged(Field::ASSIGNED_USERS . 'Ids')
            ) ||
            ($entity->hasLinkMultipleField('users') && $entity->isAttributeChanged('usersIds')) ||
            $entity->isAttributeChanged($dateAttribute);
    }

    private function toProcess(CoreEntity $entity, string $dateAttribute): bool
    {
        return $this->isNewOrChanged($entity, $dateAttribute) || $entity->has('reminders');
    }

    private function onlyRemindersFieldChanged(CoreEntity $entity, string $dateAttribute): bool
    {
        if ($this->isNewOrChanged($entity, $dateAttribute)) {
            return false;
        }

        return $entity->isAttributeChanged('reminders');
    }

    /**
     * @return string[]
     */
    private function getTypeList(): array
    {
        return $this->entityManager
            ->getDefs()
            ->getEntity(Reminder::ENTITY_TYPE)
            ->getField('type')
            ->getParam('options') ?? [];
    }

    private function deleteAll(CoreEntity $entity): void
    {
        $query = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(Reminder::ENTITY_TYPE)
            ->where([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    private function deleteAllForUser(CoreEntity $entity): void
    {
        $query = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(Reminder::ENTITY_TYPE)
            ->where([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
                'userId' => $this->user->getId(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    /**
     * @return string[]
     */
    private function getUserIdList(CoreEntity $entity): array
    {
        if ($entity->hasLinkMultipleField('users')) {
            return $entity->getLinkMultipleIdList('users');
        }

        if ($entity->hasLinkMultipleField(Field::ASSIGNED_USERS)) {
            return $entity->getLinkMultipleIdList(Field::ASSIGNED_USERS);
        }

        $userIdList = [];

        if ($entity->get('assignedUserId')) {
            $userIdList[] = $entity->get('assignedUserId');
        }

        return $userIdList;
    }

    private function getStartString(CoreEntity $entity, string $dateAttribute): ?string
    {
        $dateValue = $entity->get($dateAttribute);

        if (!$entity->has($dateAttribute)) {
            $reloadedEntity = $this->entityManager->getEntityById($entity->getEntityType(), $entity->getId());

            if ($reloadedEntity) {
                $dateValue = $reloadedEntity->get($dateAttribute);
            }

        }
        return $dateValue;
    }

    /**
     * @param string[] $typeList
     * @return object{seconds: int, type: string}[]
     */
    private function getReminderList(CoreEntity $entity, array $typeList): array
    {
        if ($entity->has('reminders')) {
            /** @var ?stdClass[] $list */
            $list = $entity->get('reminders');

            if ($list === null) {
                return [];
            }

            return $this->sanitizeList($list, $typeList);
        }

        return $this->getEntityReminderDataList($entity);
    }

    /**
     * @param string[] $typeList
     * @return object{seconds: int, type: string}[]
     */
    private function getPreferencesReminderList(array $typeList, string $userId, string $entityType): array
    {
        $preferences = $this->entityManager->getRepositoryByClass(Preferences::class)->getById($userId);

        if (!$preferences) {
            return [];
        }

        $param = 'defaultReminders';

        // @todo Refactor.
        if ($entityType === Task::ENTITY_TYPE) {
            $param = 'defaultRemindersTask';
        }

        /** @var stdClass[] $list */
        $list = $preferences->get($param) ?? [];

        return $this->sanitizeList($list, $typeList);
    }

    /**
     * @param stdClass[] $list
     * @param string[] $typeList
     * @return object{seconds: int, type: string}[]
     */
    private function sanitizeList(array $list, array $typeList): array
    {
        $result = [];

        foreach ($list as $item) {
            $seconds = ($item->seconds ?? null);
            $type = ($item->type ?? null);

            if (!is_int($seconds) || !in_array($type, $typeList)) {
                continue;
            }

            $result[] = (object) [
                'seconds' => $seconds,
                'type' => $type,
            ];
        }

        return $result;
    }

    /**
     * @param object{seconds: int, type: string} $item
     */
    private function createReminder(
        CoreEntity $entity,
        string $userId,
        DateTime $start,
        object $item
    ): void {

        $seconds = $item->seconds;
        $type = $item->type;

        $remindAt = $start->addSeconds(- $seconds);

        if ($remindAt->isLessThan(DateTime::createNow())) {
            return;
        }

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
                'id' => $this->idGenerator->generate(),
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
                'type' => $type,
                'userId' => $userId,
                'remindAt' => $remindAt->toString(),
                'startAt' => $start->toString(),
                'seconds' => $seconds,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    private function toRemove(CoreEntity $entity): bool
    {
        if (!$entity->isAttributeChanged('status')) {
            return false;
        }

        $entityType = $entity->getEntityType();

        $status = $entity->get('status');

        $ignoreStatusList = [
            ...($this->metadata->get("scopes.$entityType.completedStatusList") ?? []),
            ...($this->metadata->get("scopes.$entityType.canceledStatusList") ?? []),
        ];

        return in_array($status, $ignoreStatusList);
    }

    private function toReCreate(CoreEntity $entity): bool
    {
        if (!$entity->isAttributeChanged('status')) {
            return false;
        }

        $entityType = $entity->getEntityType();

        $statusFetched = $entity->getFetched('status');
        $status = $entity->get('status');

        $ignoreStatusList = [
            ...($this->metadata->get("scopes.$entityType.completedStatusList") ?? []),
            ...($this->metadata->get("scopes.$entityType.canceledStatusList") ?? []),
        ];

        return in_array($statusFetched, $ignoreStatusList) && !in_array($status, $ignoreStatusList);
    }
}
