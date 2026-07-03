<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;

class ScheduledJob extends Entity
{
    public const string ENTITY_TYPE = 'ScheduledJob';

    public const string STATUS_ACTIVE = 'Active';

    /**
     * @since 10.1.0
     */
    public const string FIELD_SCHEDULING = 'scheduling';
    /**
     * @since 10.1.0
     */
    public const string FIELD_STATUS = 'status';

    /**
     * @since 10.0.0
     */
    public const string FIELD_JOB = 'job';

    public function getName(): ?string
    {
        return $this->get(Field::NAME);
    }

    public function getScheduling(): ?string
    {
        return $this->get(self::FIELD_SCHEDULING);
    }

    public function getJob(): ?string
    {
        return $this->get(self::FIELD_JOB);
    }

    /**
     * @since 10.0.0
     */
    public function setActive(): self
    {
        return $this->set(self::FIELD_STATUS, self::STATUS_ACTIVE);
    }

    /**
     * @since 10.0.0
     */
    public function setName(string $name): self
    {
        return $this->set(Field::NAME, $name);
    }

    /**
     * @since 10.0.0
     */
    public function setScheduling(string $scheduling): self
    {
        return $this->set(self::FIELD_SCHEDULING, $scheduling);
    }

    /**
     * @since 10.0.0
     */
    public function setJob(string $job): self
    {
        return $this->set(self::FIELD_JOB, $job);
    }
}
