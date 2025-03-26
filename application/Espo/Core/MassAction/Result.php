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

namespace Espo\Core\MassAction;

use RuntimeException;

/**
 * Immutable.
 */
class Result
{
    private ?int $count = null;
    /** @var ?string[] */
    private $ids = null;

    /**
     * @param ?string[] $ids
     */
    public function __construct(?int $count, ?array $ids = null)
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

    /**
     * @return string[]
     */
    public function getIds(): array
    {
        if (!$this->hasIds()) {
            throw new RuntimeException("No IDs.");
        }

        /** @var string[] */
        return $this->ids;
    }

    public function getCount(): int
    {
        if (!$this->hasCount()) {
            throw new RuntimeException("No count.");
        }

        /** @var int */
        return $this->count;
    }

    public function withNoIds(): self
    {
        return new self($this->count);
    }

    /**
     * @deprecated
     * @param array{
     *   count?: ?int,
     *   ids?: ?string[],
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['count'] ?? null,
            $data['ids'] ?? null
        );
    }
}
