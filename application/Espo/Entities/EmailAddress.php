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
use Espo\Core\ORM\Entity;

use InvalidArgumentException;

class EmailAddress extends Entity
{
    public const ENTITY_TYPE = 'EmailAddress';

    public const RELATION_ENTITY_EMAIL_ADDRESS = 'EntityEmailAddress';

    /**
     * @param string $value
     * @return void
     */
    protected function _setName($value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException("Not valid email address '{$value}'");
        }

        $this->setInContainer(Field::NAME, $value);

        $this->set('lower', strtolower($value));
    }

    public function getAddress(): string
    {
        return $this->get(Field::NAME);
    }

    public function getLower(): string
    {
        return $this->get('lower');
    }

    public function isOptedOut(): bool
    {
        return $this->get('optOut');
    }

    public function isInvalid(): bool
    {
        return $this->get('invalid');
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

    public function setAddress(string $address): self
    {
        $this->set(Field::NAME, $address);

        return $this;
    }
}
