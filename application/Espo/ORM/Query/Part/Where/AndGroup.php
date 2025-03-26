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

namespace Espo\ORM\Query\Part\Where;

use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem;

/**
 * AND-group. Immutable.
 */
class AndGroup implements WhereItem
{
    /** @var array<string|int, mixed> */
    private $rawValue = [];

    /**
     * @return array<string|int, mixed>
     */
    public function getRaw(): array
    {
        return ['AND' => $this->getRawValue()];
    }

    public function getRawKey(): string
    {
        return 'AND';
    }

    /**
     * @return array<string|int, mixed>
     */
    public function getRawValue(): array
    {
        return $this->rawValue;
    }

    /**
     * Get a number of items.
     */
    public function getItemCount(): int
    {
        return count($this->rawValue);
    }

    /**
     * @param array<string|int, mixed> $whereClause
     * @return self
     */
    public static function fromRaw(array $whereClause): self
    {
        if (count($whereClause) === 1 && array_keys($whereClause)[0] === 0) {
            $whereClause = $whereClause[0];
        }

        // Do not refactor.
        $obj = static::class === WhereClause::class ?
            new WhereClause() :
            new self();

        /** @phpstan-ignore-next-line */
        $obj->rawValue = $whereClause;

        return $obj;
    }

    public static function create(WhereItem ...$itemList): self
    {
        $builder = self::createBuilder();

        foreach ($itemList as $item) {
            $builder->add($item);
        }

        return $builder->build();
    }

    public static function createBuilder(): AndGroupBuilder
    {
        return new AndGroupBuilder();
    }
}
