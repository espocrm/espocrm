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

namespace Espo\Classes\Jobs;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Job\Job\Status as JobStatus;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Entities\ActionHistoryRecord;
use Espo\Entities\ArrayValue;
use Espo\Entities\Attachment;
use Espo\Entities\AuthLogRecord;
use Espo\Entities\AuthToken;
use Espo\Entities\Job;
use Espo\Entities\Note;
use Espo\Entities\Notification;
use Espo\Entities\ScheduledJob;
use Espo\Entities\ScheduledJobLogRecord;
use Espo\Entities\UniqueId;
use Espo\Entities\UserReaction;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\DeleteBuilder;
use Espo\ORM\Repository\RDBRepository;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\InjectableFactory;
use Espo\Core\Job\JobDataLess;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;

use DateTime;
use RuntimeException;
use SplFileInfo;
use Exception;
use Throwable;

class Cleanup implements JobDataLess
{
    private string $cleanupJobPeriod = '10 days';
    private string $cleanupActionHistoryPeriod = '15 days';
    private string $cleanupAuthTokenPeriod = '1 month';
    private string $cleanupAuthLogPeriod = '2 months';
    private string $cleanupNotificationsPeriod = '2 months';
    private string $cleanupAttachmentsPeriod = '15 days';
    private string $cleanupAttachmentsFromPeriod = '6 months';
    private string $cleanupBackupPeriod = '2 month';
    private string $cleanupDeletedRecordsPeriod = '2 months';

    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private Metadata $metadata,
        private FileManager $fileManager,
        private InjectableFactory $injectableFactory,
        private SelectBuilderFactory $selectBuilderFactory,
        private ServiceContainer $recordServiceContainer,
        private Log $log
    ) {}

    public function run(): void
    {
        $this->cleanupJobs();
        $this->cleanupScheduledJobLog();
        $this->cleanupAttachments();
        $this->cleanupNotifications();
        $this->cleanupActionHistory();
        $this->cleanupAuthToken();
        $this->cleanupAuthLog();
        $this->cleanupUpgradeBackups();
        $this->cleanupUniqueIds();
        $this->cleanupDeletedRecords();

        $items = $this->metadata->get(['app', 'cleanup']) ?? [];

        usort($items, function ($a, $b) {
            $o1 = $a['order'] ?? 0;
            $o2 = $b['order'] ?? 0;

            return $o1 <=> $o2;
        });

        $injectableFactory = $this->injectableFactory;

        foreach ($items as $name => $item) {
            try {
                /** @var class-string<\Espo\Core\Cleanup\Cleanup> $className */
                $className = $item['className'];

                $obj = $injectableFactory->create($className);

                $obj->process();
            } catch (Throwable $e) {
                $this->log->error("Cleanup: $name: " . $e->getMessage());
            }
        }
    }

    private function cleanupJobs(): void
    {
        $delete = $this->entityManager->getQueryBuilder()->delete()
            ->from(Job::ENTITY_TYPE)
            ->where([
                'modifiedAt<' => $this->getCleanupJobFromDate(),
                'status!=' => JobStatus::PENDING,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        $delete = $this->entityManager->getQueryBuilder()->delete()
            ->from(Job::ENTITY_TYPE)
            ->where([
                'modifiedAt<' => $this->getCleanupJobFromDate(),
                'status=' => JobStatus::PENDING,
                Attribute::DELETED => true,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function cleanupUniqueIds(): void
    {
        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(UniqueId::ENTITY_TYPE)
            ->where([
                'terminateAt!=' => null,
                'terminateAt<' => date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function cleanupScheduledJobLog(): void
    {
        /** @var iterable<ScheduledJobLogRecord> $scheduledJobList */
        $scheduledJobList = $this->entityManager
            ->getRDBRepository(ScheduledJob::ENTITY_TYPE)
            ->select([Attribute::ID])
            ->find();

        foreach ($scheduledJobList as $scheduledJob) {
            $scheduledJobId = $scheduledJob->getId();

            /** @var iterable<ScheduledJobLogRecord> $ignoreLogRecordList */
            $ignoreLogRecordList = $this->entityManager
                ->getRDBRepository(ScheduledJobLogRecord::ENTITY_TYPE)
                ->select([Attribute::ID])
                ->where([
                    'scheduledJobId' => $scheduledJobId,
                ])
                ->order(Field::CREATED_AT, 'DESC')
                ->limit(0, 10)
                ->find();

            if (!is_countable($ignoreLogRecordList)) {
                continue;
            }

            if (!count($ignoreLogRecordList)) {
                continue;
            }

            $ignoreIdList = [];

            foreach ($ignoreLogRecordList as $logRecord) {
                $ignoreIdList[] = $logRecord->getId();
            }

            $delete = $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from(ScheduledJobLogRecord::ENTITY_TYPE)
                ->where([
                    'scheduledJobId' => $scheduledJobId,
                    'createdAt<' => $this->getCleanupJobFromDate(),
                    'id!=' => $ignoreIdList,
                ])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($delete);
        }
    }

    private function cleanupActionHistory(): void
    {
        $period = '-' . $this->config->get('cleanupActionHistoryPeriod', $this->cleanupActionHistoryPeriod);

        $datetime = $this->createDateTimeFromPeriod($period);

        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(ActionHistoryRecord::ENTITY_TYPE)
            ->where([
                'createdAt<' => $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function cleanupAuthToken(): void
    {
        $period = '-' . $this->config->get('cleanupAuthTokenPeriod', $this->cleanupAuthTokenPeriod);

        $datetime = $this->createDateTimeFromPeriod($period);

        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(AuthToken::ENTITY_TYPE)
            ->where([
                'modifiedAt<' => $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
                'isActive' => false,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function cleanupAuthLog(): void
    {
        $period = '-' . $this->config->get('cleanupAuthLogPeriod', $this->cleanupAuthLogPeriod);

        $datetime = $this->createDateTimeFromPeriod($period);

        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(AuthLogRecord::ENTITY_TYPE)
            ->where([
                'createdAt<' => $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function getCleanupJobFromDate(): string
    {
        $period = '-' . $this->config->get('cleanupJobPeriod', $this->cleanupJobPeriod);

        $datetime = $this->createDateTimeFromPeriod($period);

        return $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
    }

    private function cleanupAttachments(): void
    {
        $period = '-' . $this->config->get('cleanupAttachmentsPeriod', $this->cleanupAttachmentsPeriod);

        $datetime = $this->createDateTimeFromPeriod($period);

        $collection = $this->entityManager
            ->getRDBRepository(Attachment::ENTITY_TYPE)
            ->sth()
            ->where([
                'OR' => [
                    [
                        'role' => [
                            Attachment::ROLE_EXPORT_FILE,
                            'Mail Merge',
                            'Mass Pdf',
                        ]
                    ]
                ],
                'createdAt<' => $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->limit(0, 5000)
            ->find();

        foreach ($collection as $entity) {
            $this->entityManager->removeEntity($entity);
        }

        if ($this->config->get('cleanupOrphanAttachments')) {
            try {
                $orphanQueryBuilder = $this->selectBuilderFactory
                    ->create()
                    ->from(Attachment::ENTITY_TYPE)
                    ->withPrimaryFilter('orphan')
                    ->buildQueryBuilder();
            } catch (BadRequest|Forbidden $e) {
                throw new RuntimeException('', 0, $e);
            }

            $orphanQueryBuilder->where([
                'createdAt<' => $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
                'createdAt>' => '2018-01-01 00:00:00',
            ]);

            $collection = $this->entityManager
                ->getRDBRepository(Attachment::ENTITY_TYPE)
                ->clone($orphanQueryBuilder->build())
                ->sth()
                ->limit(0, 5000)
                ->find();

            foreach ($collection as $entity) {
                $this->entityManager->removeEntity($entity);
            }
        }

        $fromPeriod = '-' . $this->config->get('cleanupAttachmentsFromPeriod', $this->cleanupAttachmentsFromPeriod);

        $datetimeFrom = $this->createDateTimeFromPeriod($fromPeriod);

        /** @var string[] $scopeList */
        $scopeList = array_keys($this->metadata->get(['scopes']));

        foreach ($scopeList as $scope) {
            if (!$this->metadata->get(['scopes', $scope, 'entity'])) {
                continue;
            }

            if (!$this->metadata->get(['scopes', $scope, 'object']) && $scope !== Note::ENTITY_TYPE) {
                continue;
            }

            if (!$this->metadata->get(['entityDefs', $scope, 'fields', Field::MODIFIED_AT])) {
                continue;
            }

            $hasAttachmentField = false;

            if ($scope === Note::ENTITY_TYPE) {
                $hasAttachmentField = true;
            }

            if (!$hasAttachmentField) {
                foreach ($this->metadata->get(['entityDefs', $scope, 'fields']) as $defs) {
                    if (empty($defs['type'])) {
                        continue;
                    }

                    if (
                        in_array($defs['type'], [
                            FieldType::FILE,
                            FieldType::IMAGE,
                            FieldType::ATTACHMENT_MULTIPLE,
                        ])
                    ) {
                        $hasAttachmentField = true;

                        break;
                    }
                }
            }

            if (!$hasAttachmentField) {
                continue;
            }

            if (!$this->entityManager->hasRepository($scope)) {
                continue;
            }

            $repository = $this->entityManager->getRepository($scope);

            if (!method_exists($repository, 'find')) {
                continue;
            }

            if (!method_exists($repository, 'clone')) {
                continue;
            }

            $query = $this->entityManager
                ->getQueryBuilder()
                ->select(['id'])
                ->from($scope)
                ->withDeleted()
                ->where([
                    Attribute::DELETED => true,
                    'modifiedAt<' => $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
                    'modifiedAt>' => $datetimeFrom->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
                ])
                ->build();

            $deletedEntities = $repository
                ->clone($query)
                ->sth()
                ->find();

            foreach ($deletedEntities as $deletedEntity) {
                $attachmentToRemoveList = $this->entityManager
                    ->getRDBRepository(Attachment::ENTITY_TYPE)
                    ->sth()
                    ->where([
                        'OR' => [
                            [
                                'relatedType' => $scope,
                                'relatedId' => $deletedEntity->getId(),
                            ],
                            [
                                'parentType' => $scope,
                                'parentId' => $deletedEntity->getId(),
                            ]
                        ]
                    ])
                    ->find();

                foreach ($attachmentToRemoveList as $attachmentToRemove) {
                    $this->entityManager->removeEntity($attachmentToRemove);
                }
            }
        }

        $isBeingUploadedCollection = $this->entityManager
            ->getRDBRepository(Attachment::ENTITY_TYPE)
            ->sth()
            ->where([
                'isBeingUploaded' => true,
                'createdAt<' => $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->find();

        foreach ($isBeingUploadedCollection as $e) {
            $this->entityManager->removeEntity($e);
        }

        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(Attachment::ENTITY_TYPE)
            ->where([
                Attribute::DELETED => true,
                'createdAt<' => $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }


    private function cleanupNotifications(): void
    {
        $period = '-' . $this->config->get('cleanupNotificationsPeriod', $this->cleanupNotificationsPeriod);

        $datetime = $this->createDateTimeFromPeriod($period);

        /** @var iterable<Notification> $notifications */
        $notifications = $this->entityManager
            ->getRDBRepository(Notification::ENTITY_TYPE)
            ->sth()
            ->where(['createdAt<' => $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT)])
            ->find();

        foreach ($notifications as $notification) {
            $this->entityManager
                ->getRDBRepository(Notification::ENTITY_TYPE)
                ->deleteFromDb($notification->getId());
        }
    }

    private function cleanupUpgradeBackups(): void
    {
        $path = 'data/.backup/upgrades';

        $datetime = $this->createDateTimeFromPeriod('-' . $this->cleanupBackupPeriod);

        $fileManager = $this->fileManager;

        if ($fileManager->exists($path)) {
            /** @var string[] $fileList */
            $fileList = $fileManager->getFileList($path, false, '', false);

            foreach ($fileList as $dirName) {
                $dirPath = $path .  '/' . $dirName;

                $info = new SplFileInfo($dirPath);

                if ($datetime->getTimestamp() > $info->getMTime()) {
                    $fileManager->removeInDir($dirPath, true);
                }
            }
        }
    }

    private function cleanupDeletedEntity(Entity $entity): void
    {
        $scope = $entity->getEntityType();

        if (!$entity->get(Attribute::DELETED)) {
            return;
        }

        $repository = $this->entityManager->getRepository($scope);

        if (
            !$repository instanceof RDBRepository ||
            !$entity instanceof CoreEntity
        ) {
            return;
        }

        $repository->deleteFromDb($entity->getId());

        foreach ($entity->getRelationList() as $relation) {
            if ($entity->getRelationType($relation) !== Entity::MANY_MANY) {
                continue;
            }

            try {
                $relationName = $entity->getRelationParam($relation, RelationParam::RELATION_NAME);

                if (!$relationName) {
                    continue;
                }

                $midKey = $entity->getRelationParam($relation, RelationParam::MID_KEYS)[0];

                if (!$midKey) {
                    continue;
                }

                $where = [
                    $midKey => $entity->getId(),
                ];

                $conditions = $entity->getRelationParam($relation, RelationParam::CONDITIONS) ?? [];

                foreach ($conditions as $key => $value) {
                    $where[$key] = $value;
                }

                $relationEntityType = ucfirst($relationName);

                if (!$this->entityManager->hasRepository($relationEntityType)) {
                    continue;
                }

                $delete = $this->entityManager
                    ->getQueryBuilder()
                    ->delete()
                    ->from($relationEntityType)
                    ->where($where)
                    ->build();

                $this->entityManager->getQueryExecutor()->execute($delete);
            } catch (Exception $e) {
                $this->log->error("Cleanup: " . $e->getMessage());
            }
        }

        $this->cleanupEntityNotes($entity);
        $this->cleanupEntityAttachments($entity);

        if ($scope === Note::ENTITY_TYPE) {
            $this->cleanupNoteReactions($entity);
        }

        $this->cleanupEntityArrayValues($entity);
    }

    private function cleanupDeletedRecords(): void
    {
        if (!$this->config->get('cleanupDeletedRecords')) {
            return;
        }

        $period = '-' . $this->config->get('cleanupDeletedRecordsPeriod', $this->cleanupDeletedRecordsPeriod);

        $datetime = $this->createDateTimeFromPeriod($period);

        /** @var string[] $scopeList */
        $scopeList = array_keys($this->metadata->get(['scopes']));

        foreach ($scopeList as $scope) {
            if (!$this->metadata->get(['scopes', $scope, 'entity'])) {
                continue;
            }

            if ($scope === Attachment::ENTITY_TYPE) {
                continue;
            }

            if (!$this->entityManager->hasRepository($scope)) {
                continue;
            }

            $repository = $this->entityManager->getRepository($scope);

            if (!$repository instanceof RDBRepository) {
                continue;
            }

            $service = $this->recordServiceContainer->get($scope);

            $whereClause = [Attribute::DELETED => true];

            if (
                !$this->entityManager
                    ->getDefs()
                    ->getEntity($scope)
                    ->hasAttribute(Attribute::DELETED)
            ) {
                continue;
            }

            if ($this->metadata->get(['entityDefs', $scope, 'fields', Field::MODIFIED_AT])) {
                $whereClause['modifiedAt<'] = $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
            } else if ($this->metadata->get(['entityDefs', $scope, 'fields', Field::CREATED_AT])) {
                $whereClause['createdAt<'] = $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
            }

            $query = $this->entityManager
                ->getQueryBuilder()
                ->select()
                ->from($scope)
                ->withDeleted()
                ->build();

            $deletedEntityList = $repository
                ->clone($query)
                ->select([Attribute::ID, Attribute::DELETED])
                ->where($whereClause)
                ->find();

            foreach ($deletedEntityList as $entity) {
                if (method_exists($service, 'cleanup')) {
                    try {
                        $service->cleanup($entity->getId());
                    } catch (Throwable $e) {
                        $this->log
                            ->error("Cleanup job: Cleanup scope $scope: " . $e->getMessage(), ['exception' => $e]);
                    }
                }

                $this->cleanupDeletedEntity($entity);
            }
        }
    }

    private function createDateTimeFromPeriod(string $period): DateTime
    {
        $datetime = new DateTime();

        try {
            $datetime->modify($period);
        } catch (Exception $e) { /** @phpstan-ignore-line */
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        return $datetime;
    }

    private function cleanupEntityAttachments(CoreEntity $entity): void
    {
        // @todo Add file, image types support.

        $attachments = $this->entityManager
            ->getRDBRepository(Attachment::ENTITY_TYPE)
            ->where([
                'parentId' => $entity->getId(),
                'parentType' => $entity->getEntityType(),
            ])
            ->find();

        foreach ($attachments as $attachment) {
            $this->entityManager->removeEntity($attachment);

            $this->entityManager
                ->getRDBRepository(Attachment::ENTITY_TYPE)
                ->deleteFromDb($attachment->getId());
        }
    }

    private function cleanupEntityNotes(CoreEntity $entity): void
    {
        $scope = $entity->getEntityType();

        $query = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from(Note::ENTITY_TYPE)
            ->withDeleted()
            ->build();

        $noteList = $this->entityManager
            ->getRDBRepository(Note::ENTITY_TYPE)
            ->clone($query)
            ->sth()
            ->where([
                'OR' => [
                    [
                        'relatedType' => $scope,
                        'relatedId' => $entity->getId(),
                    ],
                    [
                        'parentType' => $scope,
                        'parentId' => $entity->getId(),
                    ]
                ]
            ])
            ->find();

        foreach ($noteList as $note) {
            $this->entityManager->removeEntity($note);

            $note->set(Attribute::DELETED, true);

            $this->cleanupDeletedEntity($note);
        }
    }

    private function cleanupNoteReactions(CoreEntity $entity): void
    {
        // @todo If ever reactions are supported not only for notes, then move out of the if-block.

        $deleteReactionsQuery = DeleteBuilder::create()
            ->from(UserReaction::ENTITY_TYPE)
            ->where([
                'parentId' => $entity->getId(),
                'parentType' => Note::ENTITY_TYPE,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($deleteReactionsQuery);
    }

    private function cleanupEntityArrayValues(CoreEntity $entity): void
    {
        $arrayValues = $this->entityManager
            ->getRDBRepository(ArrayValue::ENTITY_TYPE)
            ->sth()
            ->where([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
            ])
            ->find();

        foreach ($arrayValues as $arrayValue) {
            $this->entityManager
                ->getRDBRepository(ArrayValue::ENTITY_TYPE)
                ->deleteFromDb($arrayValue->getId());
        }
    }
}
