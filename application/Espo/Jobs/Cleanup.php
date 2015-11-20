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
    protected $period = '-1 month';

    public function run()
    {
        $this->cleanupJobs();
        $this->cleanupScheduledJobLog();
        $this->cleanupAttachments();
        $this->cleanupEmails();
        $this->cleanupNotes();
        $this->cleanupNotifications();
    }

    protected function cleanupJobs()
    {
        $query = "DELETE FROM `job` WHERE DATE(modified_at) < '".$this->getCleanupFromDate()."' AND status <> 'Pending'";

        $pdo = $this->getEntityManager()->getPDO();
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

            $lastRowsSql = "SELECT id FROM scheduled_job_log_record WHERE scheduled_job_id = '".$id."' ORDER BY created_at DESC LIMIT 0,10";
            $lastRowsSth = $pdo->prepare($lastRowsSql);
            $lastRowsSth->execute();
            $lastRowIds = $lastRowsSth->fetchAll(\PDO::FETCH_COLUMN, 0);

            $delSql = "DELETE FROM `scheduled_job_log_record`
                    WHERE scheduled_job_id = '".$id."'
                    AND DATE(created_at) < '".$this->getCleanupFromDate()."'
                    AND id NOT IN ('".implode("', '", $lastRowIds)."')
                ";
            $pdo->query($delSql);
        }
    }

    protected function getCleanupFromDate()
    {
        $format = 'Y-m-d';

        $datetime = new \DateTime();
        $datetime->modify($this->period);
        return $datetime->format($format);
    }

    protected function cleanupAttachments()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $dateBefore = date('Y-m-d H:i:s', time() - 3600 * 24);

        $collection = $this->getEntityManager()->getRepository('Attachment')->where(array(
            'role' => ['Export File'],
            'createdAt<' => $dateBefore
        ))->limit(0, 100)->find();

        foreach ($collection as $e) {
            $this->getEntityManager()->removeEntity($e);
        }

        $sql = "DELETE FROM attachment WHERE deleted = 1 AND created_at < ".$pdo->quote($dateBefore);
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

    protected function cleanupNotes()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $dateBefore = date('Y-m-d H:i:s', time() - 3600 * 24 * 20);

        $sql = "SELECT * FROM `note` WHERE deleted = 1 AND created_at < ".$pdo->quote($dateBefore);
        $sth = $pdo->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            $attachments = $this->getEntityManager()->getRepository('Attachment')->where(array(
                'parentId' => $id,
                'parentType' => 'Note'
            ))->find();
            foreach ($attachments as $attachment) {
                $this->getEntityManager()->removeEntity($attachment);
            }
            $sqlDel = "DELETE FROM `note` WHERE deleted = 1 AND id = ".$pdo->quote($id);
            $pdo->query($sqlDel);
        }
    }

    protected function cleanupNotifications()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $dateBefore = date('Y-m-d H:i:s', time() - 3600 * 24 * 50);

        $sql = "SELECT * FROM `notification` WHERE created_at < ".$pdo->quote($dateBefore);
        $sth = $pdo->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            $this->getEntityManager()->getRepository('Notification')->deleteFromDb($id);
        }
    }
}

