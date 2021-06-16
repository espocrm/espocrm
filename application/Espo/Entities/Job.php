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

namespace Espo\Entities;

use Espo\Core\ORM\Entity;

use StdClass;

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
    public function getData(): StdClass
    {
        return $this->get('data') ?? (object) [];
    }

    /**
     * Get a class name.
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
}
