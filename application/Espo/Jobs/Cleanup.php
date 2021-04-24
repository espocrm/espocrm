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

namespace Espo\Jobs;

use Espo\Core\Exceptions;

use Espo\Core\{
    Utils\Config,
    ORM\EntityManager,
    Jobs\Job,
    Utils\Metadata,
    Utils\File\Manager as FileManager,
    InjectableFactory,
    Select\SelectManagerFactory,
    ServiceFactory,
};

use Espo\ORM\Entity;

use DateTime;
use SplFileInfo;
use Exception;
use Throwable;

class Cleanup implements Job
{
    protected $cleanupJobPeriod = '10 days';
    protected $cleanupActionHistoryPeriod = '15 days';
    protected $cleanupAuthTokenPeriod = '1 month';
    protected $cleanupAuthLogPeriod = '2 months';
    protected $cleanupNotificationsPeriod = '2 months';
    protected $cleanupAttachmentsPeriod = '15 days';
    protected $cleanupAttachmentsFromPeriod = '3 months';
    protected $cleanupBackupPeriod = '2 month';
    protected $cleanupDeletedRecordsPeriod = '3 months';

    protected $config;
    protected $entityManager;
    protected $metadata;
    protected $fileManager;
    protected $injectableFactory;
    protected $selectManagerFactory;
    protected $serviceFactory;

    public function __construct(
        Config $config,
        EntityManager $entityManager,
        Metadata $metadata,
        FileManager $fileManager,
        InjectableFactory $injectableFactory,
        SelectManagerFactory $selectManagerFactory,
        ServiceFactory $serviceFactory
    ) {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->injectableFactory = $injectableFactory;
        $this->selectManagerFactory = $selectManagerFactory;
        $this->serviceFactory = $serviceFactory;
    }

    public function run()
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
                $GLOBALS['log']->error("Cleanup: {$name}: " . $e->getMessage());
            }
        }
    }

    protected function cleanupJobs()
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

    protected function cleanupUniqueIds()
    {
        $delete = $this->entityManager->getQueryBuilder()->delete()
            ->from('UniqueId')
            ->where([
                'terminateAt!=' => null,
                'terminateAt<' => date('Y-m-d H:i:s')
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    protected function cleanupScheduledJobLog()
    {
        $scheduledJobList = $this->entityManager->getRepository('ScheduledJob')
            ->select(['id'])
            ->find();

        foreach ($scheduledJobList as $scheduledJob) {
            $scheduledJobId = $scheduledJob->get('id');

            $ignoreLogRecordList = $this->entityManager->getRepository('ScheduledJobLogRecord')
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

            $delete = $this->entityManager->getQueryBuilder()->delete()
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

    protected function cleanupActionHistory()
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

    protected function cleanupAuthToken()
    {
        $period = '-' . $this->config->get('cleanupAuthTokenPeriod', $this->cleanupAuthTokenPeriod);

        $datetime = new DateTime();
        $datetime->modify($period);

        $delete = $this->entityManager->getQueryBuilder()->delete()
            ->from('AuthToken')
            ->where([
                'DATE:modifiedAt<' => $datetime->format('Y-m-d'),
                'isActive' => false,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    protected function cleanupAuthLog()
    {
        $period = '-' . $this->config->get('cleanupAuthLogPeriod', $this->cleanupAuthLogPeriod);

        $datetime = new DateTime();

        $datetime->modify($period);

        $delete = $this->entityManager->getQueryBuilder()->delete()
            ->from('AuthLogRecord')
            ->where([
                'DATE:createdAt<' => $datetime->format('Y-m-d'),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    protected function getCleanupJobFromDate()
    {
        $period = '-' . $this->config->get('cleanupJobPeriod', $this->cleanupJobPeriod);

        $datetime = new DateTime();

        $datetime->modify($period);

        return $datetime->format('Y-m-d');
    }

    protected function cleanupAttachments()
    {
        $pdo = $this->entityManager->getPDO();

        $period = '-' . $this->config->get('cleanupAttachmentsPeriod', $this->cleanupAttachmentsPeriod);

        $datetime = new DateTime();

        $datetime->modify($period);

        $collection = $this->entityManager
            ->getRepository('Attachment')
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
            $selectManager = $this->selectManagerFactory->create('Attachment');

            $selectParams = $selectManager->getEmptySelectParams();
            $selectManager->applyFilter('orphan', $selectParams);

            $selectParams['whereClause'][] = [
                'createdAt<' => $datetime->format('Y-m-d H:i:s'),
                'createdAt>' => '2018-01-01 00:00:00',
            ];

            $collection = $this->entityManager->getRepository('Attachment')->limit(0, 5000)->find($selectParams);

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

            $query = $this->entityManager->getQueryBuilder()
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
                    ->getRepository('Attachment')
                    ->where([
                        'OR' => [
                            [
                                'relatedType' => $scope,
                                'relatedId' => $deletedEntity->id,
                            ],
                            [
                                'parentType' => $scope,
                                'parentId' => $deletedEntity->id,
                            ]
                        ]
                    ])
                    ->find();

                foreach ($attachmentToRemoveList as $attachmentToRemove) {
                    $this->entityManager->removeEntity($attachmentToRemove);
                }
            }
        }

        $delete = $this->entityManager->getQueryBuilder()->delete()
            ->from('Attachment')
            ->where([
                'deleted' => true,
                'createdAt<' => $datetime->format('Y-m-d H:i:s'),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    protected function cleanupEmails()
    {
        $dateBefore = date('Y-m-d H:i:s', time() - 3600 * 24 * 20);

        $query = $this->entityManager->getQueryBuilder()
            ->select()
            ->from('Email')
            ->withDeleted()
            ->build();

        $emailList = $this->entityManager
            ->getRepository('Email')
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
                ->getRepository('Attachment')
                ->where([
                    'parentId' => $id,
                    'parentType' => 'Email'
                ])
                ->find();

            foreach ($attachments as $attachment) {
                $this->entityManager->removeEntity($attachment);
            }

            $delete = $this->entityManager->getQueryBuilder()->delete()
                ->from('Email')
                ->where([
                    'deleted' => true,
                    'id' => $id,
                ])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($delete);

            $delete = $this->entityManager->getQueryBuilder()->delete()
                ->from('EmailUser')
                ->where([
                    'emailId' => $id,
                ])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($delete);
        }
    }

    protected function cleanupNotifications()
    {
        $period = '-' . $this->config->get('cleanupNotificationsPeriod', $this->cleanupNotificationsPeriod);

        $datetime = new DateTime();
        $datetime->modify($period);

        $notificationList = $this->entityManager->getRepository('Notification')
            ->where([
                'DATE:createdAt<' => $datetime->format('Y-m-d'),
            ])
            ->find();

        foreach ($notificationList as $notification) {
            $this->entityManager->getRepository('Notification')->deleteFromDb($notification->get('id'));
        }
    }

    protected function cleanupUpgradeBackups()
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

    protected function cleanupDeletedEntity(Entity $entity)
    {
        $scope = $entity->getEntityType();

        if (!$entity->get('deleted')) {
            return;
        }

        $repository = $this->entityManager->getRepository($scope);

        $repository->deleteFromDb($entity->id);

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
                    $midKey => $entity->id,
                ];

                $conditions = $entity->getRelationParam($relation, 'conditions') ?? [];

                foreach ($conditions as $key => $value) {
                    $where[$key] = $value;
                }

                if (empty($where)) {
                    continue;
                }

                $relationEntityType = ucfirst($relationName);

                if (!$this->entityManager->hasRepository($relationEntityType)) {
                    continue;
                }

                $delete = $this->entityManager->getQueryBuilder()->delete()
                    ->from($relationEntityType)
                    ->where($where)
                    ->build();

                $this->entityManager->getQueryExecutor()->execute($delete);
            }
            catch (Exception $e) {
                $GLOBALS['log']->error("Cleanup: " . $e->getMessage());
            }
        }

        $query = $this->entityManager->getQueryBuilder()
            ->select()
            ->from('Note')
            ->withDeleted()
            ->build();

        $noteList = $this->entityManager
            ->getRepository('Note')
            ->clone($query)
            ->where([
                'OR' => [
                    [
                        'relatedType' => $scope,
                        'relatedId' => $entity->id,
                    ],
                    [
                        'parentType' => $scope,
                        'parentId' => $entity->id,
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
                ->getRepository('Attachment')
                ->where([
                    'parentId' => $entity->id,
                    'parentType' => 'Note',
                ])
                ->find();

            foreach ($attachmentList as $attachment) {
                $this->entityManager->removeEntity($attachment);
                $this->entityManager->getRepository('Attachment')->deleteFromDb($attachment->id);
            }
        }

        $arrayValueList = $this->entityManager
            ->getRepository('ArrayValue')
            ->where([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->id,
            ])
            ->find();

        foreach ($arrayValueList as $arrayValue) {
            $this->entityManager->getRepository('ArrayValue')->deleteFromDb($arrayValue->id);
        }
    }

    protected function cleanupDeletedRecords()
    {
        if (!$this->config->get('cleanupDeletedRecords')) {
            return;
        }

        $period = '-' . $this->config->get('cleanupDeletedRecordsPeriod', $this->cleanupDeletedRecordsPeriod);

        $datetime = new DateTime($period);

        $serviceFactory = $this->serviceFactory;

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

            if (!$repository) {
                continue;
            }

            if (!method_exists($repository, 'find')) continue;
            if (!method_exists($repository, 'clone')) continue;
            if (!method_exists($repository, 'where')) continue;
            if (!method_exists($repository, 'select')) continue;
            if (!method_exists($repository, 'deleteFromDb')) continue;

            $hasCleanupMethod = false;
            $service = null;

            if ($serviceFactory->checkExists($scope)) {
                $service = $serviceFactory->create($scope);

                if (method_exists($service, 'cleanup')) {
                    $hasCleanupMethod = true;
                }
            }

            $whereClause = [
                'deleted' => 1,
            ];

            if ($this->metadata->get(['entityDefs', $scope, 'fields', 'modifiedAt'])) {
                $whereClause['modifiedAt<'] = $datetime->format('Y-m-d H:i:s');
            } else
            if ($this->metadata->get(['entityDefs', $scope, 'fields', 'createdAt'])) {
                $whereClause['createdAt<'] = $datetime->format('Y-m-d H:i:s');
            }

            $query = $this->entityManager->getQueryBuilder()
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
                if ($hasCleanupMethod) {
                    try {
                        $service->cleanup($entity->id);
                    }
                    catch (Throwable $e) {
                        $GLOBALS['log']->error("Cleanup job: Cleanup scope {$scope}: " . $e->getMessage());
                    }
                }

                $this->cleanupDeletedEntity($entity);
            }
        }
    }
}
