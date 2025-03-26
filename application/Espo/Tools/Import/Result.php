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

namespace Espo\Tools\Import;

use stdClass;

/**
 * Immutable.
 */
class Result
{
    private ?string $id = null;
    private int $countCreated = 0;
    private int $countUpdated = 0;
    private int $countError = 0;
    private int $countDuplicate = 0;
    private bool $manualMode = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCountCreated(): int
    {
        return $this->countCreated;
    }

    public function getCountUpdated(): int
    {
        return $this->countUpdated;
    }

    public function getCountError(): int
    {
        return $this->countError;
    }

    public function getCountDuplicate(): int
    {
        return $this->countDuplicate;
    }

    public function isManualMode(): bool
    {
        return $this->manualMode;
    }

    public static function create(): self
    {
        return new self();
    }

    public function withId(?string $id): self
    {
        $obj = clone $this;
        $obj->id = $id;

        return $obj;
    }

    public function withCountCreated(int $value): self
    {
        $obj = clone $this;
        $obj->countCreated = $value;

        return $obj;
    }

    public function withCountUpdated(int $value): self
    {
        $obj = clone $this;
        $obj->countUpdated = $value;

        return $obj;
    }

    public function withCountError(int $value): self
    {
        $obj = clone $this;
        $obj->countError = $value;

        return $obj;
    }

    public function withCountDuplicate(int $value): self
    {
        $obj = clone $this;
        $obj->countDuplicate = $value;

        return $obj;
    }

    public function withManualMode(bool $manualMode = true): self
    {
        $obj = clone $this;
        $obj->manualMode = $manualMode;

        return $obj;
    }

    public function getValueMap(): stdClass
    {
        return (object) [
            'id' => $this->id,
            'countCreated' => $this->countCreated,
            'countUpdated' => $this->countUpdated,
            'manualMode' => $this->manualMode,
        ];
    }
}
