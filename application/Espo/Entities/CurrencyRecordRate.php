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
use ValueError;

class CurrencyRecordRate extends Entity
{
    public const string ENTITY_TYPE = 'CurrencyRecordRate';

    public const string FIELD_DATE = 'date';
    public const string FIELD_BASE_CODE = 'baseCode';
    public const string FIELD_RATE = 'rate';
    public const string FIELD_RECORD = 'record';

    public const string ATTR_RECORD_ID = 'recordId';

    /**
     * @return numeric-string
     */
    public function getRate(): string
    {
        /** @var numeric-string */
        return $this->get(self::FIELD_RATE) ?? '1';
    }

    /**
     * @param numeric-string $rate
     */
    public function setRate(string $rate): self
    {
        return $this->set(self::FIELD_RATE, $rate);
    }

    public function setBaseCode(string $code): self
    {
        return $this->set(self::FIELD_BASE_CODE, $code);
    }

    public function setRecord(CurrencyRecord $record): self
    {
        return $this->setRelatedLinkOrEntity(self::FIELD_RECORD, $record);
    }

    public function setDate(Date $date): self
    {
        return $this->setValueObject(self::DATE, $date);
    }

    public function getRecord(): CurrencyRecord
    {
        $record = $this->relations->getOne(self::FIELD_RECORD);

        if (!$record instanceof CurrencyRecord) {
            throw new ValueError("No record.");
        }

        return $record;
    }

    public function getDate(): Date
    {
        $date = $this->getValueObject(self::FIELD_DATE);

        if (!$date instanceof Date) {
            throw new ValueError("No date.");
        }

        return $date;
    }
}
