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

use Espo\Core\Field\LinkMultiple;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;
use Espo\ORM\EntityCollection;

class Pipeline extends Entity
{
    public const string ENTITY_TYPE = 'Pipeline';

    public const string FIELD_STATUS = 'status';
    public const string FIELD_ENTITY_TYPE = 'entityType';
    public const string FIELD_FIELD = 'field';
    public const string FIELD_COLOR = 'color';
    public const string FIELD_IS_AVAILABLE_FOR_ALL = 'isAvailableForAll';
    public const string FIELD_ORDER = 'order';

    public const string LINK_STAGES = 'stages';

    public const string STATUS_ACTIVE = 'Active';

    public function getName(): string
    {
        return $this->get(Field::NAME);
    }

    public function getTargetEntityType(): string
    {
        return $this->get(self::FIELD_ENTITY_TYPE);
    }

    public function getTargetField(): string
    {
        return $this->get(self::FIELD_FIELD);
    }

    public function getColor(): ?int
    {
        return $this->get(self::FIELD_COLOR);
    }

    public function isAvailableForAll(): bool
    {
        return $this->get(self::FIELD_IS_AVAILABLE_FOR_ALL);
    }

    public function setTeams(LinkMultiple $teams): self
    {
        return $this->setValueObject(Field::TEAMS, $teams);
    }

    public function getTeams(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject(Field::TEAMS);
    }

    public function getOrder(): int
    {
        return (int) $this->get(self::FIELD_ORDER);
    }

    /**
     * @return EntityCollection<PipelineStage>
     */
    public function getStages(): EntityCollection
    {
        /** @var EntityCollection<PipelineStage> */
        return $this->relations->getMany(self::LINK_STAGES);
    }
}
