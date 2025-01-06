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

namespace Espo\Entities;

use Espo\Core\Job\Job as JobJob;
use Espo\Core\Job\Job\Status;
use Espo\Core\Job\JobDataLess;
use Espo\Core\ORM\Entity;
use Espo\Core\Utils\DateTime as DateTimeUtil;

use stdClass;

class Job extends Entity
{
    public const ENTITY_TYPE = 'Job';

    /**
     * Get a status.
     */
    public function getStatus(): string
    {
        return $this->get('status');
    }

    /**
     * Get a job name.
     */
    public function getJob(): ?string
    {
        return $this->get('job');
    }

    /**
     * Get a scheduled job name.
     */
    public function getScheduledJobJob(): ?string
    {
        return $this->get('scheduledJobJob');
    }

    /**
     * Get a target type.
     */
    public function getTargetType(): ?string
    {
        return $this->get('targetType');
    }

    /**
     * Get a target ID.
     */
    public function getTargetId(): ?string
    {
        return $this->get('targetId');
    }

    /**
     * Get a target group.
     */
    public function getTargetGroup(): ?string
    {
        return $this->get('targetGroup');
    }

    /**
     * Get a group.
     */
    public function getGroup(): ?string
    {
        return $this->get('group');
    }

    /**
     * Get a queue.
     */
    public function getQueue(): ?string
    {
        return $this->get('queue');
    }

    /**
     * Get data.
     */
    public function getData(): stdClass
    {
        return $this->get('data') ?? (object) [];
    }

    /**
     * Get a class name.
     *
     * @return ?class-string<JobJob|JobDataLess>
     */
    public function getClassName(): ?string
    {
        return $this->get('className');
    }

    /**
     * Get a service name.
     */
    public function getServiceName(): ?string
    {
        return $this->get('serviceName');
    }

    /**
     * Get a method name.
     */
    public function getMethodName(): ?string
    {
        return $this->get('methodName');
    }

    /**
     * Get a scheduled job ID.
     */
    public function getScheduledJobId(): ?string
    {
        return $this->get('scheduledJobId');
    }

    /**
     * Get a started date-time.
     */
    public function getStartedAt(): ?string
    {
        return $this->get('startedAt');
    }

    /**
     * Get a PID.
     */
    public function getPid(): ?int
    {
        return $this->get('pid');
    }

    /**
     * Get a number of attempts left.
     */
    public function getAttempts(): int
    {
        return $this->get('attempts') ?? 0;
    }

    /**
     * Get a number of failed attempts.
     */
    public function getFailedAttempts(): int
    {
        return $this->get('failedAttempts') ?? 0;
    }

    /**
     * Set status.
     *
     * @param Status::* $status
     */
    public function setStatus(string $status): self
    {
        return $this->set('status', $status);
    }

    /**
     * Set PID.
     */
    public function setPid(?int $pid): self
    {
        return $this->set('pid', $pid);
    }

    /**
     * Set started-at to now.
     */
    public function setStartedAtNow(): self
    {
        return $this->set('startedAt', DateTimeUtil::getSystemNowString());
    }

    /**
     * Set executed-at to now.
     */
    public function setExecutedAtNow(): self
    {
        return $this->set('executedAt', DateTimeUtil::getSystemNowString());
    }
}
