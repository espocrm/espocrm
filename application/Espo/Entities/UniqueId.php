<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

use Espo\Core\Field\DateTime;
use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;

use stdClass;
use LogicException;

class UniqueId extends Entity
{
    public const ENTITY_TYPE = 'UniqueId';

    public function getIdValue(): ?string
    {
        return $this->get(Field::NAME);
    }

    public function getTerminateAt(): ?DateTime
    {
        /** @var ?DateTime */
        return $this->getValueObject('terminateAt');
    }

    public function getData(): stdClass
    {
        return $this->get('data') ?? (object) [];
    }

    public function getCreatedAt(): DateTime
    {
        /** @var ?DateTime $value */
        $value = $this->getValueObject(Field::CREATED_AT);

        if (!$value) {
            throw new LogicException();
        }

        return $value;
    }

    /**
     * @param array<string, mixed>|stdClass $data
     */
    public function setData(array|stdClass $data): self
    {
        $this->set('data', $data);

        return $this;
    }

    public function setTarget(?LinkParent $target): self
    {
        if (!$target) {
            $this->setMultiple([
                'targetId' => null,
                'targetType' => null,
            ]);
        } else {
            $this->setMultiple([
                'targetId' => $target->getId(),
                'targetType' => $target->getEntityType(),
            ]);
        }

        return $this;
    }

    public function setTerminateAt(?DateTime $terminateAt): self
    {
        $this->setValueObject('terminateAt', $terminateAt);

        return $this;
    }
}
