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

namespace Espo\Core\Job;

use Espo\Core\{
    Utils\System,
    ORM\EntityManager,
    Utils\DateTime as DateTimeUtil,
};

use Espo\{
    Entities\Job as JobEntity,
};

use Espo\Core\Job\Job\Status;

use Spatie\Async\Pool as AsyncPool;

class QueueProcessor
{
    private $params;

    private $queueUtil;

    private $jobRunner;

    private $asyncPoolFactory;

    private $entityManager;

    public function __construct(
        QueueProcessorParams $params,
        QueueUtil $queueUtil,
        JobRunner $jobRunner,
        AsyncPoolFactory $asyncPoolFactory,
        EntityManager $entityManager
    ) {
        $this->params = $params;
        $this->queueUtil = $queueUtil;
        $this->jobRunner = $jobRunner;
        $this->asyncPoolFactory = $asyncPoolFactory;
        $this->entityManager = $entityManager;
    }

    public function process(): void
    {
        $pool = null;

        if ($this->params->useProcessPool()) {
            $pool = $this->asyncPoolFactory->create();
        }

        $pendingJobList = $this->queueUtil->getPendingJobList(
            $this->params->getQueue(),
            $this->params->getGroup(),
            $this->params->getLimit()
        );

        foreach ($pendingJobList as $job) {
            $this->processJob($job, $pool);
        }

        if ($pool) {
            $pool->wait();
        }
    }

    protected function processJob(JobEntity $job, ?AsyncPool $pool = null): void
    {
        $useProcessPool = $this->params->useProcessPool();

        $noLock = $this->params->noLock();

        $lockTable = (bool) $job->getScheduledJobId() && !$noLock;

        $skip = false;

        if (!$noLock) {
            if ($lockTable) {
                // MySQL doesn't allow to lock non-existent rows. We resort to locking an entire table.
                $this->entityManager->getLocker()->lockExclusive(JobEntity::ENTITY_TYPE);
            }
            else {
                $this->entityManager->getTransactionManager()->start();
            }
        }

        if ($noLock || $this->queueUtil->isJobPending($job->id)) {
            if (
                $job->getScheduledJobId() &&
                $this->queueUtil->isScheduledJobRunning(
                    $job->getScheduledJobId(),
                    $job->getTargetId(),
                    $job->getTargetType(),
                    $job->getTargetGroup()
                )
            ) {
                $skip = true;
            }
        } else {
            $skip = true;
        }

        if ($skip && !$noLock) {
            if ($lockTable) {
                $this->entityManager->getLocker()->rollback();
            }
            else {
                $this->entityManager->getTransactionManager()->rollback();
            }
        }

        if ($skip) {
            return;
        }

        $job->set('startedAt', date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT));

        if ($useProcessPool) {
            $job->set('status', Status::READY);
        }
        else {
            $job->set('status', Status::RUNNING);
            $job->set('pid', System::getPid());
        }

        $this->entityManager->saveEntity($job);

        if (!$noLock) {
            if ($lockTable) {
                $this->entityManager->getLocker()->commit();
            }
            else {
                $this->entityManager->getTransactionManager()->commit();
            }
        }

        if ($useProcessPool) {
            $task = new JobTask($job->id);

            $pool->add($task);

            return;
        }

        $this->jobRunner->run($job);
    }
}