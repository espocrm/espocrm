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

use Espo\Core\Field\Date;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;
use UnexpectedValueException;

class OAuthClientSecret extends Entity
{
    public const string ENTITY_TYPE = 'OAuthClientSecret';

    public const string FIELD_STATUS = 'status';
    public const string FIELD_HASH = 'hash';
    public const string FILED_CLIENT = 'client';
    public const string FIELD_EXPIRATION_DATE = 'expirationDate';
    public const string FIELD_VALUE = 'value';

    public const string STATUS_ACTIVE = 'Active';
    public const string STATUS_REVOKED = 'Revoked';
    public const string STATUS_EXPIRED = 'Expired';

    public function setName(string $name): self
    {
        return $this->set(Field::NAME, $name);
    }

    public function getName(): ?string
    {
        return $this->get(Field::NAME);
    }

    public function setHash(string $value): self
    {
        return $this->set(self::FIELD_HASH, $value);
    }

    public function isActive(): bool
    {
        return $this->get(self::FIELD_STATUS) === self::STATUS_ACTIVE;
    }

    public function setRevoked(): self
    {
        return $this->set(self::FIELD_STATUS, self::STATUS_REVOKED);
    }

    public function setExpired(): self
    {
        return $this->set(self::FIELD_STATUS, self::STATUS_EXPIRED);
    }

    public function getExpirationData(): ?Date
    {
        /** @var ?Date */
        return $this->getValueObject(self::FIELD_EXPIRATION_DATE);
    }

    public function setExpirationData(?Date $date): self
    {
        return $this->setValueObject(self::FIELD_EXPIRATION_DATE, $date);
    }

    public function getClient(): OAuthClient
    {
        $app = $this->relations->getOne(self::FILED_CLIENT);

        if (!$app instanceof OAuthClient) {
            throw new UnexpectedValueException("No client.");
        }

        return $app;
    }

    public function setClient(OAuthClient $app): self
    {
        return $this->setRelatedLinkOrEntity(self::FILED_CLIENT, $app);
    }

    public function setValue(string $value): self
    {
        return $this->set(self::FIELD_VALUE, $value);
    }
}
