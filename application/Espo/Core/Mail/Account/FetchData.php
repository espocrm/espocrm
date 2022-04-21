<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Mail\Account;

use Espo\Core\Utils\ObjectUtil;
use Espo\Core\Field\DateTime;

use stdClass;
use RuntimeException;

class FetchData
{
    private stdClass $data;

    public function __construct(stdClass $data)
    {
        $this->data = ObjectUtil::clone($data);
    }

    public static function fromRaw(stdClass $data): self
    {
        return new self($data);
    }

    public function getRaw(): stdClass
    {
        return ObjectUtil::clone($this->data);
    }

    public function getLastUniqueId(string $folder): ?string
    {
        return $this->data->lastUID->$folder ?? null;
    }

    public function getLastDate(string $folder): ?DateTime
    {
        $value = $this->data->lastDate->$folder ?? null;

        if ($value === null) {
            return null;
        }

        // For backward compatibility.
        if ($value === 0) {
            return null;
        }

        if (!is_string($value)) {
            throw new RuntimeException("Bad value in fetch-data.");
        }

        return DateTime::fromString($value);
    }

    public function getForceByDate(string $folder): bool
    {
        return $this->data->byDate->$folder ?? false;
    }

    public function setLastUniqueId(string $folder, ?string $uniqueId): void
    {
        if (!property_exists($this->data, 'lastUID')) {
            $this->data->lastUID = (object) [];
        }

        $this->data->lastUID->$folder = $uniqueId;
    }

    public function setLastDate(string $folder, ?DateTime $lastDate): void
    {
        if (!property_exists($this->data, 'lastDate')) {
            $this->data->lastDate = (object) [];
        }

        if ($lastDate === null) {
            $this->data->lastDate->$folder = null;

            return;
        }

        $this->data->lastDate->$folder = $lastDate->getString();
    }

    public function setForceByDate(string $folder, bool $forceByDate): void
    {
        if (!property_exists($this->data, 'byDate')) {
            $this->data->byDate = (object) [];
        }

        $this->data->byDate->$folder = $forceByDate;
    }
}
