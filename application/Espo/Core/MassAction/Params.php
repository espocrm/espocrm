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

use Espo\Core\{
    Select\SearchParams,
};

use RuntimeException;

class Params
{
    private $entityType;

    private $ids;

    private $searchParams;

    private function __construct()
    {
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getIds(): array
    {
        if (!$this->ids) {
            throw new RuntimeException("No IDs.");
        }

        return $this->ids;
    }

    public function getSearchParams(): SearchParams
    {
        if (!$this->searchParams) {
            throw new RuntimeException("No search params.");
        }

        return $this->searchParams;
    }

    public function hasIds(): bool
    {
        return !is_null($this->ids);
    }

    public static function createWithIds(string $entityType, array $ids): self
    {
        return self::fromRaw([
            'entityType' => $entityType,
            'ids' => $ids,
        ]);
    }

    public static function createWithSearchParams(string $entityType, SearchParams $searchParams): self
    {
        $obj = new self();

        $obj->entityType = $entityType;

        $obj->searchParams = $searchParams;

        return $obj;
    }

    /**
     * Create from raw params.
     *
     * @throws RuntimeException
     */
    public static function fromRaw(array $params, ?string $entityType = null): self
    {
        $obj = new self();

        $obj->entityType = $entityType ?? $params['entityType'] ?? null;

        if (!$obj->entityType) {
            throw new RuntimeException("No 'entityType'.");
        }

        $where = $params['where'] ?? null;
        $ids = $params['ids'] ?? null;

        $searchParams = $params['searchParams'] ?? $params['selectData'] ?? null;

        if ($where !== null && !is_array($where)) {
            throw new RuntimeException("Bad 'where'.");
        }

        if ($searchParams !== null && !is_array($searchParams)) {
            throw new RuntimeException("Bad 'searchParams'.");
        }

        if ($where !== null && $searchParams !== null) {
            $searchParams['where'] = $where;
        }

        if ($where !== null && $searchParams === null) {
            $searchParams = [
                'where' => $where,
            ];
        }

        if ($searchParams !== null) {
            if ($ids !== null) {
                throw new RuntimeException("Can't combine 'ids' and search params.");
            }
        }
        else if ($ids !== null) {
            if (!is_array($ids)) {
                throw new RuntimeException("Bad 'ids'.");
            }

            $obj->ids = $ids;
        }
        else {
            throw new RuntimeException("Bad mass action params.");
        }

        if ($searchParams !== null) {
            $actualSearchParams = $searchParams;

            unset($actualSearchParams['select']);

            $obj->searchParams = SearchParams::fromRaw($actualSearchParams);
        }

        return $obj;
    }
}
