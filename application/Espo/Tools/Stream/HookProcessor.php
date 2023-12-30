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

namespace Espo\Tools\Stream;

use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\Metadata;
use Espo\Core\Job\QueueName;
use Espo\Core\Job\JobSchedulerFactory;

use Espo\Entities\Autofollow;
use Espo\Entities\User;
use Espo\Entities\Preferences;

use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\ORM\Defs\RelationDefs;

use Espo\ORM\Repository\Option\RemoveOptions;
use Espo\Tools\Stream\Service as Service;
use Espo\Tools\Stream\Jobs\AutoFollow as AutoFollowJob;
use Espo\Tools\Stream\Jobs\ControlFollowers as ControlFollowersJob;

/**
 * Handles operations with entities.
 */
class HookProcessor
{
    /** @var array<string, bool> */
    private $hasStreamCache = [];
    /** @var array<string, bool> */
    private $isLinkObservableInStreamCache = [];
    /** @var ?array<string, ?string> */
    private $statusFields = null;

    public function __construct(
        private Metadata $metadata,
        private EntityManager $entityManager,
        private Service $service,
        private User $user,
        private Preferences $preferences,
        private JobSchedulerFactory $jobSchedulerFactory
    ) {}

    /**
     * @param array<string, mixed> $options
     */
    public function afterSave(Entity $entity, array $options): void
    {
        if ($this->checkHasStream($entity->getEntityType())) {
            $this->afterSaveStream($entity, $options);
        }

        if (
            $entity->isNew() &&
            empty($options['noStream']) &&
            empty($options[SaveOption::SILENT]) &&
            $this->metadata->get(['scopes', $entity->getEntityType(), 'object'])
        ) {
            $this->handleCreateRelated($entity, $options);
        }
    }

    public function afterRemove(Entity $entity, RemoveOptions $options): void
    {
        if ($this->checkHasStream($entity->getEntityType())) {
            $this->service->unfollowAllUsersFromEntity($entity);
        }
    }

    private function checkHasStream(string $entityType): bool
    {
        if (!array_key_exists($entityType, $this->hasStreamCache)) {
            $this->hasStreamCache[$entityType] = (bool) $this->metadata->get(['scopes', $entityType, 'stream']);
        }

        return $this->hasStreamCache[$entityType];
    }

    private function isLinkObservableInStream(string $entityType, string $link): bool
    {
        $key = $entityType . '__' . $link;

        if (!array_key_exists($key, $this->isLinkObservableInStreamCache)) {
            $this->isLinkObservableInStreamCache[$key] =
                (bool) $this->metadata->get(['scopes', $entityType, 'stream']) &&
                (bool) $this->metadata->get(['entityDefs', $entityType, 'links', $link, 'audited']);
        }

        return $this->isLinkObservableInStreamCache[$key];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function handleCreateRelated(Entity $entity, array $options = []): void
    {
        $notifiedEntityTypeList = [];

        $relationList = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType())
            ->getRelationList();

        foreach ($relationList as $relation) {
            $type = $relation->getType();

            if ($type === Entity::BELONGS_TO) {
                $this->handleCreateRelatedBelongsTo($entity, $relation, $notifiedEntityTypeList);

                continue;
            }

            if ($type === Entity::BELONGS_TO_PARENT) {
                $this->handleCreateRelatedBelongsToParent($entity, $relation, $notifiedEntityTypeList, $options);

                continue;
            }

            if ($type === Entity::HAS_MANY) {
                $this->handleCreateRelatedHasMany($entity, $relation, $notifiedEntityTypeList, $options);

                continue;
            }
        }
    }

    /**
     * @param string[] $notifiedEntityTypeList
     */
    private function handleCreateRelatedBelongsTo(
        Entity $entity,
        RelationDefs $defs,
        array &$notifiedEntityTypeList
    ): void {

        if (
            !$defs->hasForeignRelationName() ||
            !$defs->hasForeignEntityType()
        ) {
            return;
        }

        $link = $defs->getName();
        $foreign = $defs->getForeignRelationName();
        $foreignEntityType = $defs->getForeignEntityType();

        if (in_array($foreignEntityType, $notifiedEntityTypeList)) {
            return;
        }

        $id = $entity->get($link . 'Id');

        if (!$id) {
            return;
        }

        if (!$this->isLinkObservableInStream($foreignEntityType, $foreign)) {
            return;
        }

        $this->service->noteCreateRelated($entity, $foreignEntityType, $id);

        $notifiedEntityTypeList[] = $foreignEntityType;
    }

    /**
     * @param string[] $notifiedEntityTypeList
     * @param array<string, mixed> $options
     */
    private function handleCreateRelatedBelongsToParent(
        Entity $entity,
        RelationDefs $defs,
        array &$notifiedEntityTypeList,
        array $options
    ): void {

        if (!$defs->hasForeignRelationName()) {
            return;
        }

        $link = $defs->getName();
        $foreign = $defs->getForeignRelationName();

        $foreignEntityType = $entity->get($link . 'Type');
        $id = $entity->get($link . 'Id');

        if (!$foreignEntityType || !$id) {
            return;
        }

        if (in_array($foreignEntityType, $notifiedEntityTypeList)) {
            return;
        }

        if (!$this->isLinkObservableInStream($foreignEntityType, $foreign)) {
            return;
        }

        $this->service->noteCreateRelated($entity, $foreignEntityType, $id, $options);

        $notifiedEntityTypeList[] = $foreignEntityType;
    }

    /**
     * @param string[] $notifiedEntityTypeList
     * @param array<string, mixed> $options
     */
    private function handleCreateRelatedHasMany(
        Entity $entity,
        RelationDefs $defs,
        array &$notifiedEntityTypeList,
        array $options
    ): void {

        if (
            !$defs->hasForeignRelationName() ||
            !$defs->hasForeignEntityType()
        ) {
            return;
        }

        $link = $defs->getName();
        $foreign = $defs->getForeignRelationName();
        $foreignEntityType = $defs->getForeignEntityType();

        if (in_array($foreignEntityType, $notifiedEntityTypeList)) {
            return;
        }

        if (!$entity->hasAttribute($link . 'Ids')) {
            return;
        }

        $ids = $entity->get($link . 'Ids');

        if (!is_array($ids) || !count($ids)) {
            return;
        }

        if (!$this->isLinkObservableInStream($foreignEntityType, $foreign)) {
            return;
        }

        $id = $ids[0];

        $this->service->noteCreateRelated($entity, $foreignEntityType, $id, $options);

        $notifiedEntityTypeList[] = $foreignEntityType;
    }

    /**
     * @param string[] $ignoreUserIdList
     * @return string[]
     */
    private function getAutofollowUserIdList(string $entityType, array $ignoreUserIdList = []): array
    {
        $userIdList = [];

        $autofollowList = $this->entityManager
            ->getRDBRepository(Autofollow::ENTITY_TYPE)
            ->select(['userId'])
            ->where([
                'entityType' => $entityType,
            ])
            ->find();

        foreach ($autofollowList as $autofollow) {
            $userId = $autofollow->get('userId');

            if (in_array($userId, $ignoreUserIdList)) {
                continue;
            }

            $userIdList[] = $userId;
        }

        return $userIdList;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function afterSaveStream(Entity $entity, array $options): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        if ($entity->isNew()) {
            $this->afterSaveStreamNew($entity, $options);

            return;
        }

        $this->afterSaveStreamNotNew($entity, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function afterSaveStreamNew(CoreEntity $entity, array $options): void
    {
        $entityType = $entity->getEntityType();

        $multipleField = $this->metadata->get(['streamDefs', $entityType, 'followingUsersField']) ??
            'assignedUsers';

        $hasAssignedUsersField = $entity->hasLinkMultipleField($multipleField);

        $userIdList = [];

        $assignedUserId = $entity->get('assignedUserId');
        $createdById = $entity->get('createdById');

        /** @var string[] $assignedUserIdList */
        $assignedUserIdList = $hasAssignedUsersField ? $entity->getLinkMultipleIdList($multipleField) : [];

        if (
            !$this->user->isSystem() &&
            !$this->user->isApi() &&
            $createdById &&
            $createdById === $this->user->getId() &&
            (
                $this->user->isPortal() ||
                $this->preferences->get('followCreatedEntities') ||
                in_array($entityType, $this->preferences->get('followCreatedEntityTypeList') ?? [])
            )
        ) {
            $userIdList[] = $createdById;
        }

        if ($hasAssignedUsersField) {
            $userIdList = array_unique(
                array_merge($userIdList, $assignedUserIdList)
            );
        }

        if ($assignedUserId && !in_array($assignedUserId, $userIdList)) {
            $userIdList[] = $assignedUserId;
        }

        if (count($userIdList)) {
            $this->service->followEntityMass($entity, $userIdList);
        }

        if (empty($options['noStream']) && empty($options[SaveOption::SILENT])) {
            $this->service->noteCreate($entity, $options);
        }

        if (in_array($this->user->getId(), $userIdList)) {
            $entity->set('isFollowed', true);
        }

        $autofollowUserIdList = $this->getAutofollowUserIdList($entity->getEntityType(), $userIdList);

        if (count($autofollowUserIdList)) {
            $this->jobSchedulerFactory
                ->create()
                ->setClassName(AutoFollowJob::class)
                ->setQueue(QueueName::Q1)
                ->setData([
                    'userIdList' => $autofollowUserIdList,
                    'entityType' => $entity->getEntityType(),
                    'entityId' => $entity->getId(),
                ])
                ->schedule();
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function afterSaveStreamNotNew(CoreEntity $entity, array $options): void
    {
        $this->afterSaveStreamNotNew1($entity, $options);
        $this->afterSaveStreamNotNew2($entity);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function afterSaveStreamNotNew1(CoreEntity $entity, array $options): void
    {
        if (!empty($options['noStream']) || !empty($options[SaveOption::SILENT])) {
            return;
        }

        if ($entity->isAttributeChanged('assignedUserId')) {
            $this->afterSaveStreamNotNewAssignedUserIdChanged($entity, $options);
        }

        $this->service->handleAudited($entity, $options);

        $statusField = $this->getStatusField($entity->getEntityType());

        if (
            $statusField &&
            $entity->get($statusField) &&
            $entity->isAttributeChanged($statusField)
        ) {
            $this->service->noteStatus($entity, $statusField, $options);
        }

        $multipleField = $this->metadata->get(['streamDefs', $entity->getEntityType(), 'followingUsersField']) ??
            'assignedUsers';

        if (!$entity->hasLinkMultipleField($multipleField)) {
            return;
        }

        /** @var string[] $assignedUserIdList */
        $assignedUserIdList = $entity->getLinkMultipleIdList($multipleField);
        $fetchedAssignedUserIdList = $entity->getFetched($multipleField . 'Ids') ?? [];

        foreach ($assignedUserIdList as $userId) {
            if (in_array($userId, $fetchedAssignedUserIdList)) {
                continue;
            }

            $this->service->followEntity($entity, $userId);

            if ($this->user->getId() === $userId) {
                $entity->set('isFollowed', true);
            }
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function afterSaveStreamNotNewAssignedUserIdChanged(Entity $entity, array $options): void
    {
        $assignedUserId = $entity->get('assignedUserId');

        if (!$assignedUserId) {
            $this->service->noteAssign($entity, $options);

            return;
        }

        $this->service->followEntity($entity, $assignedUserId);
        $this->service->noteAssign($entity, $options);

        if ($this->user->getId() === $assignedUserId) {
            $entity->set('isFollowed', true);
        }
    }

    private function afterSaveStreamNotNew2(CoreEntity $entity): void
    {
        $methodName = 'isChangedWithAclAffect';

        if (
            (method_exists($entity, $methodName) && $entity->$methodName()) ||
            (
                !method_exists($entity, $methodName) &&
                (
                    $entity->isAttributeChanged('assignedUserId') ||
                    $entity->isAttributeChanged('teamsIds') ||
                    $entity->isAttributeChanged('assignedUsersIds')
                )
            )
        ) {
            $this->jobSchedulerFactory
                ->create()
                ->setClassName(ControlFollowersJob::class)
                ->setQueue(QueueName::Q1)
                ->setData([
                    'entityType' => $entity->getEntityType(),
                    'entityId' => $entity->getId(),
                ])
                ->schedule();
        }
    }

    private function getStatusField(string $entityType): ?string
    {
        return $this->getStatusFields()[$entityType] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function getStatusFields(): array
    {
        if (is_null($this->statusFields)) {
            $this->statusFields = [];

            /** @var array<string, array<string, mixed>> $scopes */
            $scopes = $this->metadata->get('scopes', []);

            foreach ($scopes as $scope => $data) {
                /** @var ?string $statusField */
                $statusField = $data['statusField'] ?? null;

                if (!$statusField) {
                    continue;
                }

                $this->statusFields[$scope] = $statusField;
            }
        }

        /** @var array<string, string> */
        return $this->statusFields;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function afterRelate(Entity $entity, Entity $foreignEntity, string $link, array $options): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        $entityType = $entity->getEntityType();
        $foreignEntityType = $foreignEntity->getEntityType();

        $foreignLink = $entity->getRelationParam($link, 'foreign');

        if (
            !empty($options['noStream']) ||
            !empty($options[SaveOption::SILENT]) ||
            !$this->metadata->get(['scopes', $entityType, 'object'])
        ) {
            return;
        }

        $audited = $this->metadata->get(['entityDefs', $entityType, 'links', $link, 'audited']);
        $auditedForeign = $this->metadata->get(['entityDefs', $foreignEntityType, 'links', $foreignLink, 'audited']);

        if ($audited) {
            $this->service->noteRelate($foreignEntity, $entityType, $entity->getId());
        }

        if ($auditedForeign) {
            $this->service->noteRelate($entity, $foreignEntity->getEntityType(), $foreignEntity->getId());
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function afterUnrelate(Entity $entity, Entity $foreignEntity, string $link, array $options): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        $entityType = $entity->getEntityType();
        $foreignEntityType = $foreignEntity->getEntityType();
        $foreignLink = $entity->getRelationParam($link, 'foreign');

        if (
            !empty($options['noStream']) ||
            !empty($options[SaveOption::SILENT]) ||
            !$this->metadata->get(['scopes', $entityType, 'object'])
        ) {
            return;
        }

        $audited = $this->metadata->get(['entityDefs', $entityType, 'links', $link, 'audited']);
        $auditedForeign = $this->metadata->get(['entityDefs', $foreignEntityType, 'links', $foreignLink, 'audited']);

        if ($audited) {
            $this->service->noteUnrelate($foreignEntity, $entityType, $entity->getId());

            // @todo
            // Add time period (a few minutes). If before, remove RELATE note, don't create 'unrelate' if before.
        }

        if ($auditedForeign) {
            $this->service->noteUnrelate($entity, $foreignEntity->getEntityType(), $foreignEntity->getId());

            // @todo
            // Add time period (a few minutes). If before, remove RELATE note, don't create 'unrelate' if before.
        }
    }
}
