<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\MassAction;

use RuntimeException;

class Result
{
    private $ids = null;

    private $count = null;

    public function __construct(int $count, ?array $ids = null)
    {
        $this->count = $count;
        $this->ids = $ids;
    }

    public function hasIds(): bool
    {
        return $this->ids !== null;
    }

    public function hasCount(): bool
    {
        return $this->count !== null;
    }

    public function getIds(): array
    {
        if (!$this->hasIds()) {
            throw new RuntimeException("No IDs.");
        }

        return $this->ids;
    }

    public function getCount(): int
    {
        if (!$this->hasCount()) {
            throw new RuntimeException("No count.");
        }

        return $this->count;
    }

    public function withNoIds(): self
    {
        return self::fromArray([
            'count' => $this->count,
        ]);
    }

    public static function fromArray(array $data): self
    {
        $obj = new self(
            $data['count'] ?? null,
            $data['ids'] ?? null
        );

        return $obj;
    }
}
