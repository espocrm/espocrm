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

namespace Espo\Tools\Stream;

use Espo\Core\Utils\Metadata;
use Espo\Services\Stream as Service;
use Espo\Entities\User;
use Espo\Entities\Preferences;
use Espo\Entities\Note;

use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\ORM\Defs\RelationDefs;

use Espo\Core\Job\QueueName;
use Espo\Core\Job\JobSchedulerFactory;
use Espo\Core\Utils\DateTime as DateTimeUtil;

use Espo\Tools\Stream\Jobs\AutoFollow as AutoFollowJob;
use Espo\Tools\Stream\Jobs\ControlFollowers as ControlFollowersJob;

use Espo\Core\ORM\Entity as CoreEntity;

/**
 * Handles operations with entities.
 */
class HookProcessor
{
    private $hasStreamCache = [];

    private $isLinkObservableInStreamCache = [];

    private $statusFields = null;

    private $metadata;

    private $entityManager;

    private $service;

    private $user;

    private $preferences;

    private $jobSchedulerFactory;

    public function __construct(
        Metadata $metadata,
        EntityManager $entityManager,
        Service $service,
        User $user,
        Preferences $preferences,
        JobSchedulerFactory $jobSchedulerFactory
    ) {
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
        $this->service = $service;
        $this->user = $user;
        $this->preferences = $preferences;
        $this->jobSchedulerFactory = $jobSchedulerFactory;
    }

    public function afterSave(Entity $entity, array $options): void
    {
        if ($this->checkHasStream($entity->getEntityType())) {
            $this->afterSaveStream($entity, $options);
        }

        if (
            $entity->isNew() &&
            empty($options['noStream']) &&
            empty($options['silent']) &&
            $this->metadata->get(['scopes', $entity->getEntityType(), 'object'])
        ) {
            $this->handleCreateRelated($entity, $options);
        }
    }

    public function afterRemove(Entity $entity): void
    {
        if ($this->checkHasStream($entity->getEntityType())) {
            $this->service->unfollowAllUsersFromEntity($entity);
        }

        $query = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Note::ENTITY_TYPE)
            ->set([
                'deleted' => true,
                'modifiedAt' => date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->where([
                'relatedId' => $entity->getId(),
                'relatedType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
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
     * @return string[]
     */
    private function getAutofollowUserIdList(string $entityType, array $ignoreUserIdList = []): array
    {
        $userIdList = [];

        $autofollowList = $this->entityManager
            ->getRDBRepository('Autofollow')
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

    private function afterSaveStreamNew(CoreEntity $entity, array $options): void
    {
        $entityType = $entity->getEntityType();
        $hasAssignedUsersField = $entity->hasLinkMultipleField('assignedUsers');

        $userIdList = [];

        $assignedUserId = $entity->get('assignedUserId');
        $createdById = $entity->get('createdById');

        $assignedUserIdList = $hasAssignedUsersField ? $entity->getLinkMultipleIdList('assignedUsers') : [];

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

        if (empty($options['noStream']) && empty($options['silent'])) {
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

    private function afterSaveStreamNotNew(CoreEntity $entity, array $options): void
    {
        $this->afterSaveStreamNotNew1($entity, $options);
        $this->afterSaveStreamNotNew2($entity);
    }

    private function afterSaveStreamNotNew1(CoreEntity $entity, array $options): void
    {
        if (!empty($options['noStream']) || !empty($options['silent'])) {
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

        if (!$entity->hasLinkMultipleField('assignedUsers')) {
            return;
        }

        $assignedUserIdList = $entity->getLinkMultipleIdList('assignedUsers');
        $fetchedAssignedUserIdList = $entity->getFetched('assignedUsersIds') ?? [];

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

        return;
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

    private function getStatusFields(): array
    {
        if (is_null($this->statusFields)) {
            $this->statusFields = [];

            $scopes = $this->metadata->get('scopes', []);

            foreach ($scopes as $scope => $data) {
                if (empty($data['statusField'])) {
                    continue;
                }

                $this->statusFields[$scope] = $data['statusField'];
            }
        }

        return $this->statusFields;
    }

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
            !empty($options['silent']) ||
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
            !empty($options['silent']) ||
            !$this->metadata->get(['scopes', $entityType, 'object'])
        ) {
            return;
        }

        $audited = $this->metadata->get(['entityDefs', $entityType, 'links', $link, 'audited']);
        $auditedForeign = $this->metadata->get(['entityDefs', $foreignEntityType, 'links', $foreignLink, 'audited']);

        /** @todo Add time period. */

        if ($audited) {
            $note1 = $this->entityManager
                ->getRDBRepository('Note')
                ->where([
                    'type' => 'Relate',
                    'parentId' => $entity->getId(),
                    'parentType' => $entityType,
                    'relatedId' => $foreignEntity->getId(),
                    'relatedType' => $foreignEntityType,
                ])
                ->findOne();

            if ($note1) {
                $this->entityManager->removeEntity($note1);
            }
        }

        if ($auditedForeign) {
            $note2 = $this->entityManager
                ->getRDBRepository('Note')
                ->where([
                    'type' => 'Relate',
                    'parentId' => $foreignEntity->getId(),
                    'parentType' => $foreignEntityType,
                    'relatedId' => $entity->getId(),
                    'relatedType' => $entityType,
                ])
                ->findOne();

            if ($note2) {
                $this->entityManager->removeEntity($note2);
            }
        }
    }
}
