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

use Espo\Core\ORM\Entity;
use UnexpectedValueException;

class PipelineStage extends Entity
{
    public const string ENTITY_TYPE = 'PipelineStage';

    public const string FIELD_MAPPED_STATUS = 'mappedStatus';
    public const string FIELD_ORDER = 'order';
    public const string FIELD_PIPELINE = 'pipeline';
    public const string FIELD_NAME = 'name';

    public const string ATTR_PIPELINE_ID = 'pipelineId';

    public function getName(): string
    {
        return $this->get(self::FIELD_NAME);
    }

    public function setName(string $name): self
    {
        $this->set(self::FIELD_NAME, $name);

        return $this;
    }

    public function setMappedStatus(string $status): self
    {
        $this->set(self::FIELD_MAPPED_STATUS, $status);

        return $this;
    }

    public function getMappedStatus(): string
    {
        return $this->get(self::FIELD_MAPPED_STATUS);
    }

    public function getOrder(): int
    {
        return (int) $this->get(self::FIELD_ORDER);
    }

    public function setOrder(int $order): self
    {
        $this->set(self::FIELD_ORDER, $order);

        return $this;
    }

    public function setPipeline(Pipeline $pipeline): self
    {
        return $this->setRelatedLinkOrEntity(self::FIELD_PIPELINE, $pipeline);
    }

    public function getPipeline(): Pipeline
    {
        $pipeline = $this->relations->getOne(self::FIELD_PIPELINE);

        if (!$pipeline instanceof Pipeline) {
            throw new UnexpectedValueException("No pipeline.");
        }

        return $pipeline;
    }
}
