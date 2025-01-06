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

use Espo\Core\Name\Field;
use InvalidArgumentException;

use Espo\Core\ORM\Entity;

class PhoneNumber extends Entity
{
    public const ENTITY_TYPE = 'PhoneNumber';

    public const RELATION_ENTITY_PHONE_NUMBER = 'EntityPhoneNumber';

    /**
     * @param string $value
     * @return void
     */
    protected function _setName($value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException("Phone number can't be empty");
        }

        $this->setInContainer(Field::NAME, $value);
    }

    public function getNumber(): string
    {
        return $this->get(Field::NAME);
    }

    public function isOptedOut(): bool
    {
        return $this->get('optOut');
    }

    public function isInvalid(): bool
    {
        return $this->get('invalid');
    }

    public function getType(): ?string
    {
        return $this->get('type');
    }

    public function setType(string $type): self
    {
        $this->set('type', $type);

        return $this;
    }

    public function setNumber(string $number): self
    {
        $this->set(Field::NAME, $number);

        return $this;
    }

    public function setOptedOut(bool $optedOut): self
    {
        $this->set('optOut', $optedOut);

        return $this;
    }

    public function setInvalid(bool $invalid): self
    {
        $this->set('invalid', $invalid);

        return $this;
    }
}

