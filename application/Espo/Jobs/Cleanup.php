<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\Core\Exceptions;

class Cleanup extends \Espo\Core\Jobs\Base
{
    protected $cleanupJobPeriod = '10 days';

    protected $cleanupActionHistoryPeriod = '15 days';

    protected $cleanupAuthTokenPeriod = '1 month';

    protected $cleanupAuthLogPeriod = '2 months';

    protected $cleanupNotificationsPeriod = '2 months';

    protected $cleanupAttachmentsPeriod = '15 days';

    protected $cleanupAttachmentsFromPeriod = '3 months';

    protected $cleanupRemindersPeriod = '15 days';

    protected $cleanupBackupPeriod = '2 month';

    protected $cleanupDeletedRecordsPeriod = '3 months';

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
    }

    protected function cleanupJobs()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $query = "DELETE FROM `job` WHERE DATE(modified_at) < ".$pdo->quote($this->getCleanupJobFromDate())." AND status <> 'Pending'";
        $sth = $pdo->prepare($query);
        $sth->execute();

        $query = "DELETE FROM `job` WHERE DATE(modified_at) < ".$pdo->quote($this->getCleanupJobFromDate())." AND status = 'Pending' AND deleted = 1";
        $sth = $pdo->prepare($query);
        $sth->execute();
    }

    protected function cleanupUniqueIds()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $query = "DELETE FROM `unique_id` WHERE terminate_at IS NOT NULL AND terminate_at < ".$pdo->quote(date('Y-m-d H:i:s'))."";

        $sth = $pdo->prepare($query);
        $sth->execute();
    }

    protected function cleanupScheduledJobLog()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = "SELECT id FROM scheduled_job";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row['id'];

            $lastRowsSql = "SELECT id FROM scheduled_job_log_record WHERE scheduled_job_id = ".$pdo->quote($id)." ORDER BY created_at DESC LIMIT 0,10";
            $lastRowsSth = $pdo->prepare($lastRowsSql);
            $lastRowsSth->execute();
            $lastRowIds = $lastRowsSth->fetchAll(\PDO::FETCH_COLUMN, 0);

            if (count($lastRowIds)) {
                foreach ($lastRowIds as $i => $v) {
                    $lastRowIds[$i] = $pdo->quote($v);
                }
                $delSql = "DELETE FROM `scheduled_job_log_record`
                        WHERE scheduled_job_id = ".$pdo->quote($id)."
                        AND DATE(created_at) < ".$pdo->quote($this->getCleanupJobFromDate())."
                        AND id NOT IN (".implode(',', $lastRowIds).")
                    ";
                $pdo->query($delSql);
            }
        }
    }

    protected function cleanupActionHistory()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $period = '-' . $this->getConfig()->get('cleanupActionHistoryPeriod', $this->cleanupActionHistoryPeriod);
        $datetime = new \DateTime();
        $datetime->modify($period);

        $query = "DELETE FROM `action_history_record` WHERE DATE(created_at) < " . $pdo->quote($datetime->format('Y-m-d')) . "";

        $sth = $pdo->prepare($query);
        $sth->execute();
    }

    protected function cleanupReminders()
    {
        $period = '-' . $this->getConfig()->get('cleanupRemindersPeriod', $this->cleanupRemindersPeriod);
        $datetime = new \DateTime();
        $datetime->modify($period);

        $query = "DELETE FROM `reminder` WHERE DATE(remind_at) < " . $pdo->quote($datetime->format('Y-m-d')) . "";

        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($query);
        $sth->execute();
    }

    protected function cleanupAuthToken()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $period = '-' . $this->getConfig()->get('cleanupAuthTokenPeriod', $this->cleanupAuthTokenPeriod);
        $datetime = new \DateTime();
        $datetime->modify($period);

        $query = "DELETE FROM `auth_token` WHERE DATE(modified_at) < " . $pdo->quote($datetime->format('Y-m-d')) . " AND is_active = 0";

        $sth = $pdo->prepare($query);
        $sth->execute();
    }

    protected function cleanupAuthLog()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $period = '-' . $this->getConfig()->get('cleanupAuthLogPeriod', $this->cleanupAuthLogPeriod);
        $datetime = new \DateTime();
        $datetime->modify($period);

        $query = "DELETE FROM `auth_log_record` WHERE DATE(created_at) < " . $pdo->quote($datetime->format('Y-m-d')) . "";

        $sth = $pdo->prepare($query);
        $sth->execute();
    }

    protected function getCleanupJobFromDate()
    {
        $period = '-' . $this->getConfig()->get('cleanupJobPeriod', $this->cleanupJobPeriod);
        $datetime = new \DateTime();
        $datetime->modify($period);
        return $datetime->format('Y-m-d');
    }

    protected function cleanupAttachments()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $period = '-' . $this->getConfig()->get('cleanupAttachmentsPeriod', $this->cleanupAttachmentsPeriod);
        $datetime = new \DateTime();
        $datetime->modify($period);

        $collection = $this->getEntityManager()->getRepository('Attachment')->where(array(
            'OR' => array(
                array(
                    'role' => ['Export File', 'Mail Merge', 'Mass Pdf']
                )
            ),
            'createdAt<' => $datetime->format('Y-m-d H:i:s')
        ))->limit(0, 5000)->find();

        foreach ($collection as $e) {
            $this->getEntityManager()->removeEntity($e);
        }

        if ($this->getConfig()->get('cleanupOrphanAttachments')) {
            $collection = $this->getEntityManager()->getRepository('Attachment')->where([
                [
                    'role' => 'Attachment',
                ],
                'OR' => [
                    [
                        'parentId' => null,
                        'parentType!=' => null,
                        'relatedType=' => null,
                    ],
                    [
                        'parentType' => null,
                        'relatedId' => null,
                        'relatedType!=' => null,
                    ]
                ],
                'createdAt<' => $datetime->format('Y-m-d H:i:s'),
                'createdAt>' => '2018-01-01 00:00:00',
            ])->limit(0, 5000)->find();

            foreach ($collection as $e) {
                $this->getEntityManager()->removeEntity($e);
            }
        }

        $fromPeriod = '-' . $this->getConfig()->get('cleanupAttachmentsFromPeriod', $this->cleanupAttachmentsFromPeriod);
        $datetimeFrom = new \DateTime();
        $datetimeFrom->modify($fromPeriod);

        $scopeList = array_keys($this->getMetadata()->get(['scopes']));
        foreach ($scopeList as $scope) {
            if (!$this->getMetadata()->get(['scopes', $scope, 'entity'])) continue;
            if (!$this->getMetadata()->get(['scopes', $scope, 'object']) && $scope !== 'Note') continue;
            if (!$this->getMetadata()->get(['entityDefs', $scope, 'fields', 'modifiedAt'])) continue;

            $hasAttachmentField = false;
            if ($scope === 'Note') {
                $hasAttachmentField = true;
            }
            if (!$hasAttachmentField) {
                foreach ($this->getMetadata()->get(['entityDefs', $scope, 'fields']) as $field => $defs) {
                    if (empty($defs['type'])) continue;
                    if (in_array($defs['type'], ['file', 'image', 'attachmentMultiple'])) {
                        $hasAttachmentField = true;
                        break;
                    }
                }
            }
            if (!$hasAttachmentField) continue;

            if (!$this->getEntityManager()->hasRepository($scope)) continue;
            $repository = $this->getEntityManager()->getRepository($scope);
            if (!method_exists($repository, 'find')) continue;
            if (!method_exists($repository, 'where')) continue;

            $deletedEntityList = $repository->where([
                'deleted' => 1,
                'modifiedAt<' => $datetime->format('Y-m-d H:i:s'),
                'modifiedAt>' => $datetimeFrom->format('Y-m-d H:i:s'),

            ])->find(['withDeleted' => true]);
            foreach ($deletedEntityList as $deletedEntity) {
                $attachmentToRemoveList = $this->getEntityManager()->getRepository('Attachment')->where([
                    'OR' => [
                        [
                            'relatedType' => $scope,
                            'relatedId' => $deletedEntity->id
                        ],
                        [
                            'parentType' => $scope,
                            'parentId' => $deletedEntity->id
                        ]
                    ]
                ])->find();

                foreach ($attachmentToRemoveList as $attachmentToRemove) {
                    $this->getEntityManager()->removeEntity($attachmentToRemove);
                }
            }
        }

        $sql = "DELETE FROM attachment WHERE deleted = 1 AND created_at < ".$pdo->quote($datetime->format('Y-m-d H:i:s'));
        $sth = $pdo->query($sql);
    }

    protected function cleanupEmails()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $dateBefore = date('Y-m-d H:i:s', time() - 3600 * 24 * 20);

        $sql = "SELECT * FROM email WHERE deleted = 1 AND created_at < ".$pdo->quote($dateBefore);
        $sth = $pdo->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            $attachments = $this->getEntityManager()->getRepository('Attachment')->where(array(
                'parentId' => $id,
                'parentType' => 'Email'
            ))->find();
            foreach ($attachments as $attachment) {
                $this->getEntityManager()->removeEntity($attachment);
            }
            $sqlDel = "DELETE FROM email WHERE deleted = 1 AND id = ".$pdo->quote($id);
            $pdo->query($sqlDel);
            $sqlDel = "DELETE FROM email_user WHERE email_id = ".$pdo->quote($id);
            $pdo->query($sqlDel);
        }
    }

    protected function cleanupNotifications()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $period = '-' . $this->getConfig()->get('cleanupNotificationsPeriod', $this->cleanupNotificationsPeriod);
        $datetime = new \DateTime();
        $datetime->modify($period);

        $sql = "SELECT * FROM `notification` WHERE DATE(created_at) < ".$pdo->quote($datetime->format('Y-m-d'));

        $sth = $pdo->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            $this->getEntityManager()->getRepository('Notification')->deleteFromDb($id);
        }
    }

    protected function cleanupUpgradeBackups()
    {
        $path = 'data/.backup/upgrades';
        $datetime = new \DateTime('-' . $this->cleanupBackupPeriod);

        if (file_exists($path)) {
            $fileManager = $this->getContainer()->get('fileManager');
            $fileList = $fileManager->getFileList($path, false, '', false);

            foreach ($fileList as $dirName) {
                $dirPath = $path .  '/' . $dirName;

                $info = new \SplFileInfo($dirPath);
                if ($datetime->getTimestamp() > $info->getMTime()) {
                    $fileManager->removeInDir($dirPath, true);
                }
            }
        }
    }

    protected function cleanupDeletedEntity(\Espo\ORM\Entity $e)
    {
        $scope = $e->getEntityType();

        if (!$e->get('deleted')) return;

        $repository = $this->getEntityManager()->getRepository($scope);
        $repository->deleteFromDb($e->id);

        $query = $this->getEntityManager()->getQuery();

        foreach ($e->getRelationList() as $relation) {
            if ($e->getRelationType($relation) !== 'manyMany') continue;;
            try {
                $relationName = $e->getRelationParam($relation, 'relationName');
                $relationTable = $query->toDb($relationName);

                $midKey = $e->getRelationParam($relation, 'midKeys')[0];

                $where = [];
                $where[$midKey] = $e->id;

                $conditions = $e->getRelationParam($relation, 'conditions');
                if (!empty($conditions)) {
                    foreach ($conditions as $key => $value) {
                        $where[$key] = $value;
                    }
                }

                $partList = [];
                foreach ($where as $key => $value) {
                    $partList[] = $query->toDb($key) . ' = ' . $query->quote($value);
                }
                if (empty($partList)) continue;

                $sql = "DELETE FROM `{$relationTable}` WHERE " . implode(' AND ', $partList);

                $this->getEntityManager()->getPDO()->query($sql);
            } catch (\Exception $e) {}
        }

        $noteList = $this->getEntityManager()->getRepository('Note')->where([
            'OR' => [
                [
                    'relatedType' => $scope,
                    'relatedId' => $e->id
                ],
                [
                    'parentType' => $scope,
                    'parentId' => $e->id
                ]
            ]
        ])->find(['withDeleted' => true]);
        foreach ($noteList as $note) {
            $this->getEntityManager()->removeEntity($note);
            $note->set('deleted', true);
            $this->cleanupDeletedEntity($note);
        }

        if ($scope === 'Note') {
            $attachmentList = $this->getEntityManager()->getRepository('Attachment')->where([
                'parentId' => $e->id,
                'parentType' => 'Note'
            ])->find();
            foreach ($attachmentList as $attachment) {
                $this->getEntityManager()->removeEntity($attachment);
                $this->getEntityManager()->getRepository('Attachment')->deleteFromDb($attachment->id);
            }
        }
    }

    protected function cleanupDeletedRecords()
    {
        if (!$this->getConfig()->get('cleanupDeletedRecords')) return;
        $period = '-' . $this->getConfig()->get('cleanupDeletedRecordsPeriod', $this->cleanupDeletedRecordsPeriod);
        $datetime = new \DateTime($period);

        $serviceFactory = $this->getServiceFactory();

        $scopeList = array_keys($this->getMetadata()->get(['scopes']));
        foreach ($scopeList as $scope) {
            if (!$this->getMetadata()->get(['scopes', $scope, 'entity'])) continue;
            if ($scope === 'Attachment') continue;

            if (!$this->getEntityManager()->hasRepository($scope)) continue;
            $repository = $this->getEntityManager()->getRepository($scope);
            if (!$repository) continue;
            if (!method_exists($repository, 'find')) continue;
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

            if ($this->getMetadata()->get(['entityDefs', $scope, 'fields', 'modifiedAt'])) {
                $whereClause['modifiedAt<'] = $datetime->format('Y-m-d H:i:s');
            } else if ($this->getMetadata()->get(['entityDefs', $scope, 'fields', 'createdAt'])) {
                $whereClause['createdAt<'] = $datetime->format('Y-m-d H:i:s');
            }

            $deletedEntityList = $repository->select(['id', 'deleted'])->where($whereClause)->find(['withDeleted' => true]);
            foreach ($deletedEntityList as $e) {
                if ($hasCleanupMethod) {
                    try {
                        $service->cleanup($e->id);
                    } catch (\Throwable $e) {
                        $GLOBALS['log']->error("Cleanup job: Cleanup scope {$scope}: " . $e->getMessage());
                    }
                }
                $this->cleanupDeletedEntity($e);
            }
        }
    }
}
