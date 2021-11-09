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

namespace Espo\Classes\Jobs;

use Espo\Core\Record\ServiceContainer;
use Espo\ORM\Repository\RDBRepository;
use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Core\{
    Utils\Config,
    ORM\EntityManager,
    Job\JobDataLess,
    Utils\Metadata,
    Utils\File\Manager as FileManager,
    InjectableFactory,
    Select\SelectBuilderFactory,
    Utils\Log,
};

use Espo\ORM\Entity;

use DateTime;
use SplFileInfo;
use Exception;
use Throwable;

class Cleanup implements JobDataLess
{
    private $cleanupJobPeriod = '10 days';

    private $cleanupActionHistoryPeriod = '15 days';

    private $cleanupAuthTokenPeriod = '1 month';

    private $cleanupAuthLogPeriod = '2 months';

    private $cleanupNotificationsPeriod = '2 months';

    private $cleanupAttachmentsPeriod = '15 days';

    private $cleanupAttachmentsFromPeriod = '3 months';

    private $cleanupBackupPeriod = '2 month';

    private $cleanupDeletedRecordsPeriod = '3 months';

    private $config;

    private $entityManager;

    private $metadata;

    private $fileManager;

    private $injectableFactory;

    private $selectBuilderFactory;

    private $recordServiceContainer;

    private $log;

    public function __construct(
        Config $config,
        EntityManager $entityManager,
        Metadata $metadata,
        FileManager $fileManager,
        InjectableFactory $injectableFactory,
        SelectBuilderFactory $selectBuilderFactory,
        ServiceContainer $recordServiceContainer,
        Log $log
    ) {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->injectableFactory = $injectableFactory;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->recordServiceContainer = $recordServiceContainer;
        $this->log = $log;
    }

    public function run(): void
    {
        $this->cleanupJobs();
        $this->cleanupScheduledJobLog();
        $this->cleanupAttachments();
        $this->cleanupEmails();
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
                $className = $item['className'];

                $injectableFactory->create($className)->process();
            }
            catch (Throwable $e) {
                $this->log->error("Cleanup: {$name}: " . $e->getMessage());
            }
        }
    }

    private function cleanupJobs(): void
    {
        $delete = $this->entityManager->getQueryBuilder()->delete()
            ->from('Job')
            ->where([
                'DATE:modifiedAt<' => $this->getCleanupJobFromDate(),
                'status!=' => 'Pending',
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        $delete = $this->entityManager->getQueryBuilder()->delete()
            ->from('Job')
            ->where([
                'DATE:modifiedAt<' => $this->getCleanupJobFromDate(),
                'status=' => 'Pending',
                'deleted' => true,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function cleanupUniqueIds(): void
    {
        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from('UniqueId')
            ->where([
                'terminateAt!=' => null,
                'terminateAt<' => date('Y-m-d H:i:s')
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function cleanupScheduledJobLog(): void
    {
        $scheduledJobList = $this->entityManager
            ->getRDBRepository('ScheduledJob')
            ->select(['id'])
            ->find();

        foreach ($scheduledJobList as $scheduledJob) {
            $scheduledJobId = $scheduledJob->get('id');

            $ignoreLogRecordList = $this->entityManager
                ->getRDBRepository('ScheduledJobLogRecord')
                ->select(['id'])
                ->where([
                    'scheduledJobId' => $scheduledJobId,
                ])
                ->order('createdAt', 'DESC')
                ->limit(0, 10)
                ->find();

            if (!count($ignoreLogRecordList)) {
                continue;
            }

            $ignoreIdList = [];

            foreach ($ignoreLogRecordList as $logRecord) {
                $ignoreIdList[] = $logRecord->get('id');
            }

            $delete = $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from('ScheduledJobLogRecord')
                ->where([
                    'scheduledJobId' => $scheduledJobId,
                    'DATE:createdAt<' => $this->getCleanupJobFromDate(),
                    'id!=' => $ignoreIdList,
                ])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($delete);
        }
    }

    private function cleanupActionHistory(): void
    {
        $period = '-' . $this->config->get('cleanupActionHistoryPeriod', $this->cleanupActionHistoryPeriod);

        $datetime = new DateTime();

        $datetime->modify($period);

        $delete = $this->entityManager->getQueryBuilder()->delete()
            ->from('ActionHistoryRecord')
            ->where([
                'DATE:createdAt<' => $datetime->format('Y-m-d'),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function cleanupAuthToken(): void
    {
        $period = '-' . $this->config->get('cleanupAuthTokenPeriod', $this->cleanupAuthTokenPeriod);

        $datetime = new DateTime();
        $datetime->modify($period);

        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from('AuthToken')
            ->where([
                'DATE:modifiedAt<' => $datetime->format('Y-m-d'),
                'isActive' => false,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function cleanupAuthLog(): void
    {
        $period = '-' . $this->config->get('cleanupAuthLogPeriod', $this->cleanupAuthLogPeriod);

        $datetime = new DateTime();

        $datetime->modify($period);

        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from('AuthLogRecord')
            ->where([
                'DATE:createdAt<' => $datetime->format('Y-m-d'),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function getCleanupJobFromDate(): string
    {
        $period = '-' . $this->config->get('cleanupJobPeriod', $this->cleanupJobPeriod);

        $datetime = new DateTime();
        $datetime->modify($period);

        return $datetime->format('Y-m-d');
    }

    private function cleanupAttachments(): void
    {
        $period = '-' . $this->config->get('cleanupAttachmentsPeriod', $this->cleanupAttachmentsPeriod);

        $datetime = new DateTime();

        $datetime->modify($period);

        $collection = $this->entityManager
            ->getRDBRepository('Attachment')
            ->where([
                'OR' => [
                    [
                        'role' => ['Export File', 'Mail Merge', 'Mass Pdf']
                    ]
                ],
                'createdAt<' => $datetime->format('Y-m-d H:i:s'),
            ])
            ->limit(0, 5000)
            ->find();

        foreach ($collection as $entity) {
            $this->entityManager->removeEntity($entity);
        }

        if ($this->config->get('cleanupOrphanAttachments')) {
            $orphanQueryBuilder = $this->selectBuilderFactory
                ->create()
                ->from('Attachment')
                ->withPrimaryFilter('orphan')
                ->buildQueryBuilder();

            $orphanQueryBuilder->where([
                'createdAt<' => $datetime->format('Y-m-d H:i:s'),
                'createdAt>' => '2018-01-01 00:00:00',
            ]);

            $collection = $this->entityManager
                ->getRDBRepository('Attachment')
                ->clone($orphanQueryBuilder->build())
                ->limit(0, 5000)
                ->find();

            foreach ($collection as $entity) {
                $this->entityManager->removeEntity($entity);
            }
        }

        $fromPeriod = '-' . $this->config->get('cleanupAttachmentsFromPeriod', $this->cleanupAttachmentsFromPeriod);

        $datetimeFrom = new DateTime();

        $datetimeFrom->modify($fromPeriod);

        $scopeList = array_keys($this->metadata->get(['scopes']));

        foreach ($scopeList as $scope) {
            if (!$this->metadata->get(['scopes', $scope, 'entity'])) {
                continue;
            }

            if (!$this->metadata->get(['scopes', $scope, 'object']) && $scope !== 'Note') {
                continue;
            }

            if (!$this->metadata->get(['entityDefs', $scope, 'fields', 'modifiedAt'])) {
                continue;
            }

            $hasAttachmentField = false;

            if ($scope === 'Note') {
                $hasAttachmentField = true;
            }

            if (!$hasAttachmentField) {
                foreach ($this->metadata->get(['entityDefs', $scope, 'fields']) as $field => $defs) {
                    if (empty($defs['type'])) {
                        continue;
                    }

                    if (in_array($defs['type'], ['file', 'image', 'attachmentMultiple'])) {
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
                ->select()
                ->from($scope)
                ->withDeleted()
                ->where([
                    'deleted' => 1,
                    'modifiedAt<' => $datetime->format('Y-m-d H:i:s'),
                    'modifiedAt>' => $datetimeFrom->format('Y-m-d H:i:s'),
                ])
                ->build();

            $deletedEntityList = $repository
                ->clone($query)
                ->find();

            foreach ($deletedEntityList as $deletedEntity) {
                $attachmentToRemoveList = $this->entityManager
                    ->getRDBRepository('Attachment')
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

        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from('Attachment')
            ->where([
                'deleted' => true,
                'createdAt<' => $datetime->format('Y-m-d H:i:s'),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function cleanupEmails(): void
    {
        $dateBefore = date('Y-m-d H:i:s', time() - 3600 * 24 * 20);

        $query = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from('Email')
            ->withDeleted()
            ->build();

        $emailList = $this->entityManager
            ->getRDBRepository('Email')
            ->clone($query)
            ->select(['id'])
            ->where([
                'createdAt<' => $dateBefore,
                'deleted' => true,
            ])
            ->find();

        foreach ($emailList as $email) {
            $id = $email->get('id');

            $attachments = $this->entityManager
                ->getRDBRepository('Attachment')
                ->where([
                    'parentId' => $id,
                    'parentType' => 'Email'
                ])
                ->find();

            foreach ($attachments as $attachment) {
                $this->entityManager->removeEntity($attachment);
            }

            $delete = $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from('Email')
                ->where([
                    'deleted' => true,
                    'id' => $id,
                ])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($delete);

            $delete = $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from('EmailUser')
                ->where([
                    'emailId' => $id,
                ])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($delete);
        }
    }

    private function cleanupNotifications(): void
    {
        $period = '-' . $this->config->get('cleanupNotificationsPeriod', $this->cleanupNotificationsPeriod);

        $datetime = new DateTime();
        $datetime->modify($period);

        $notificationList = $this->entityManager
            ->getRDBRepository('Notification')
            ->where([
                'DATE:createdAt<' => $datetime->format('Y-m-d'),
            ])
            ->find();

        foreach ($notificationList as $notification) {
            $this->entityManager
                ->getRDBRepository('Notification')
                ->deleteFromDb($notification->get('id'));
        }
    }

    private function cleanupUpgradeBackups(): void
    {
        $path = 'data/.backup/upgrades';

        $datetime = new DateTime('-' . $this->cleanupBackupPeriod);

        if (file_exists($path)) {
            $fileManager = $this->fileManager;
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

        if (!$entity->get('deleted')) {
            return;
        }

        $repository = $this->entityManager->getRepository($scope);

        if (!$repository instanceof RDBRepository) {
            return;
        }

        if (!$entity instanceof CoreEntity) {
            return;
        }

        $repository->deleteFromDb($entity->getId());

        $query = $this->entityManager->getQueryComposer();

        foreach ($entity->getRelationList() as $relation) {
            if ($entity->getRelationType($relation) !== Entity::MANY_MANY) {
                continue;
            }

            try {
                $relationName = $entity->getRelationParam($relation, 'relationName');

                if (!$relationName) {
                    continue;
                }

                $midKey = $entity->getRelationParam($relation, 'midKeys')[0];

                if (!$midKey) {
                    continue;
                }

                $where = [
                    $midKey => $entity->getId(),
                ];

                $conditions = $entity->getRelationParam($relation, 'conditions') ?? [];

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
            }
            catch (Exception $e) {
                $this->log->error("Cleanup: " . $e->getMessage());
            }
        }

        $query = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from('Note')
            ->withDeleted()
            ->build();

        $noteList = $this->entityManager
            ->getRDBRepository('Note')
            ->clone($query)
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

            $note->set('deleted', true);

            $this->cleanupDeletedEntity($note);
        }

        if ($scope === 'Note') {
            $attachmentList = $this->entityManager
                ->getRDBRepository('Attachment')
                ->where([
                    'parentId' => $entity->getId(),
                    'parentType' => 'Note',
                ])
                ->find();

            foreach ($attachmentList as $attachment) {
                $this->entityManager->removeEntity($attachment);
                $this->entityManager
                    ->getRDBRepository('Attachment')
                    ->deleteFromDb($attachment->getId());
            }
        }

        $arrayValueList = $this->entityManager
            ->getRDBRepository('ArrayValue')
            ->where([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
            ])
            ->find();

        foreach ($arrayValueList as $arrayValue) {
            $this->entityManager
                ->getRDBRepository('ArrayValue')
                ->deleteFromDb($arrayValue->getId());
        }
    }

    private function cleanupDeletedRecords(): void
    {
        if (!$this->config->get('cleanupDeletedRecords')) {
            return;
        }

        $period = '-' . $this->config->get('cleanupDeletedRecordsPeriod', $this->cleanupDeletedRecordsPeriod);

        $datetime = new DateTime($period);

        $scopeList = array_keys($this->metadata->get(['scopes']));

        foreach ($scopeList as $scope) {
            if (!$this->metadata->get(['scopes', $scope, 'entity'])) {
                continue;
            }

            if ($scope === 'Attachment') {
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

            $whereClause = [
                'deleted' => 1,
            ];

            if ($this->metadata->get(['entityDefs', $scope, 'fields', 'modifiedAt'])) {
                $whereClause['modifiedAt<'] = $datetime->format('Y-m-d H:i:s');
            }
            else if ($this->metadata->get(['entityDefs', $scope, 'fields', 'createdAt'])) {
                $whereClause['createdAt<'] = $datetime->format('Y-m-d H:i:s');
            }

            $query = $this->entityManager
                ->getQueryBuilder()
                ->select()
                ->from($scope)
                ->withDeleted()
                ->build();

            $deletedEntityList = $repository
                ->clone($query)
                ->select(['id', 'deleted'])
                ->where($whereClause)
                ->find();

            foreach ($deletedEntityList as $entity) {
                if (method_exists($service, 'cleanup')) {
                    try {
                        $service->cleanup($entity->getId());
                    }
                    catch (Throwable $e) {
                        $this->log->error("Cleanup job: Cleanup scope {$scope}: " . $e->getMessage());
                    }
                }

                $this->cleanupDeletedEntity($entity);
            }
        }
    }
}
