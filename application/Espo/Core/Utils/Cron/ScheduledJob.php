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

namespace Espo\Core\Utils\Cron;

use Espo\ORM\{
    Collection,
    EntityManager,
};

class ScheduledJob
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get active scheduled job list.
     */
    public function getActiveScheduledJobList() : Collection
    {
        return $this->entityManager
            ->getRepository('ScheduledJob')
            ->select([
                'id',
                'scheduling',
                'job',
                'name',
            ])
            ->where([
                'status' => 'Active'
            ])
            ->find();
    }

    /**
     * Add record to ScheduledJobLogRecord about executed job.
     */
    public function addLogRecord(
        $scheduledJobId, $status, $runTime = null, $targetId = null, $targetType = null
    ) {

        if (!isset($runTime)) {
            $runTime = date('Y-m-d H:i:s');
        }

        $entityManager = $this->entityManager;

        $scheduledJob = $entityManager->getEntity('ScheduledJob', $scheduledJobId);

        if (!$scheduledJob) {
            return;
        }

        $scheduledJob->set('lastRun', $runTime);

        $entityManager->saveEntity($scheduledJob, ['silent' => true]);

        $scheduledJobLog = $entityManager->getEntity('ScheduledJobLogRecord');

        $scheduledJobLog->set([
            'scheduledJobId' => $scheduledJobId,
            'name' => $scheduledJob->get('name'),
            'status' => $status,
            'executionTime' => $runTime,
            'targetId' => $targetId,
            'targetType' => $targetType,
        ]);

        $entityManager->saveEntity($scheduledJobLog);
    }
}