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
use Espo\Core\ORM\Entity;

class CurrencyRecord extends Entity
{
    public const string ENTITY_TYPE = 'CurrencyRecord';

    public const string FIELD_STATUS = 'status';
    public const string FIELD_CODE = 'code';

    public const string STATUS_ACTIVE = 'Active';
    public const string STATUS_INACTIVE = 'Inactive';

    public function getCode(): string
    {
        return $this->get(self::FIELD_CODE);
    }

    public function setCode(string $code): self
    {
        return $this->set(self::FIELD_CODE, $code);
    }

    public function getStatus(): string
    {
        return $this->get(self::FIELD_STATUS);
    }

    public function setStatus(string $status): self
    {
        return $this->set(self::FIELD_STATUS, $status);
    }

    public function setLabel(?string $label): self
    {
        return $this->set('label', $label);
    }

    public function setSymbol(?string $label): self
    {
        return $this->set('symbol', $label);
    }

    public function setIsBase(bool $isBase): self
    {
        return $this->set('isBase', $isBase);
    }

    /**
     * @param ?numeric-string $rate
     */
    public function setRate(?string $rate): self
    {
        return $this->set('rate', $rate);
    }

    public function setRateDate(?Date $date): self
    {
        return $this->setValueObject('rateDate', $date);
    }
}
