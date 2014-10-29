<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\Services;

use PDO;

class ScheduledJob extends
    Record
{

    public function getActiveJobs()
    {
        $query = "SELECT * FROM scheduled_job WHERE
                    `status` = 'Active'
                    AND deleted = 0";
        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($query);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
        $list = array();
        foreach ($rows as $row) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Add record to ScheduledJobLogRecord about executed job
     *
     * @param string $scheduledJobId
     * @param string $status
     *
     * @return string Id of created ScheduledJobLogRecord
     */
    public function addLogRecord($scheduledJobId, $status)
    {
        $lastRun = date('Y-m-d H:i:s');
        $entityManager = $this->getEntityManager();
        $scheduledJob = $entityManager->getEntity('ScheduledJob', $scheduledJobId);
        $scheduledJob->set('lastRun', $lastRun);
        $entityManager->saveEntity($scheduledJob);
        $scheduledJobLog = $entityManager->getEntity('ScheduledJobLogRecord');
        $scheduledJobLog->set(array(
            'scheduledJobId' => $scheduledJobId,
            'name' => $scheduledJob->get('name'),
            'status' => $status,
            'executionTime' => $lastRun,
        ));
        $scheduledJobLogId = $entityManager->saveEntity($scheduledJobLog);
        //$entityManager->getRepository('ScheduledJobLogRecord')->relate($scheduledJobLog, 'scheduledJob', $scheduledJob);        
        return $scheduledJobLogId;
    }
}

