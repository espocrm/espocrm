<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Jobs;

use \Espo\Core\Exceptions;

class Cleanup extends \Espo\Core\Jobs\Base
{
    protected $period = '-1 month';

    public function run()
    {
        $this->cleanupJobs();
        $this->cleanupScheduledJobLog();
    }

    protected function cleanupJobs()
    {
        $query = "DELETE FROM `job` WHERE DATE(modified_at) < '".$this->getDate()."' ";

        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($query);
        $sth->execute();
    }

    protected function cleanupScheduledJobLog()
    {
        $lastTenRecords = "SELECT c.id FROM (
            SELECT i1.id
            FROM scheduled_job_log_record i1
            CROSS JOIN scheduled_job_log_record i2 ON ( i1.scheduled_job_id = i2.scheduled_job_id
            AND i1.id < i2.id )
            GROUP BY i1.id
            HAVING COUNT( * ) <10
            ORDER BY i1.created_at DESC
        ) AS c";

        $query = "DELETE FROM `scheduled_job_log_record` WHERE DATE(created_at) < '".$this->getDate()."' AND id NOT IN (".$lastTenRecords.") ";

        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($query);
        $sth->execute();
    }

    protected function getDate($format = 'Y-m-d')
    {
        $datetime = new \DateTime();
        $datetime->modify($this->period);
        return $datetime->format($format);
    }
}

