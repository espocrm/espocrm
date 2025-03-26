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

use Espo\Core\Select\SearchParams;

use RuntimeException;

/**
 * Immutable.
 */
class Params
{
    private string $entityType;
    /** @var ?string[] */
    private ?array $ids = null;
    private ?SearchParams $searchParams = null;

    private function __construct() {}

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @return string[]
     * @throws RuntimeException
     */
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

    /**
     * @param string[] $ids
     */
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
     * @param array{
     *   entityType?: string,
     *   where?: array<int, array<string, mixed>>,
     *   ids?: ?string[],
     *   searchParams?: ?array<string, mixed>,
     * } $params
     * @throws RuntimeException
     */
    public static function fromRaw(array $params, ?string $entityType = null): self
    {
        /** @var array<string, mixed> $params */

        $obj = new self();

        $passedEntityType = $entityType ?? $params['entityType'] ?? null;

        if (!$passedEntityType) {
            throw new RuntimeException("No 'entityType'.");
        }

        $obj->entityType = $passedEntityType;

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
        } else if ($ids !== null) {
            if (!is_array($ids)) {
                throw new RuntimeException("Bad 'ids'.");
            }

            $obj->ids = $ids;
        } else {
            throw new RuntimeException("Bad mass action params.");
        }

        if ($searchParams !== null) {
            $actualSearchParams = $searchParams;

            unset($actualSearchParams['select']);

            $obj->searchParams = SearchParams::fromRaw($actualSearchParams);
        }

        return $obj;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        if ($this->searchParams) {
            $this->searchParams = clone $this->searchParams;
        }
    }

    /**
     * @return array{
     *   entityType: string,
     *   ids: ?string[],
     *   searchParams: string,
     * }
     */
    public function __serialize(): array
    {
        return [
            'entityType' => $this->entityType,
            'ids' => $this->ids,
            'searchParams' => serialize($this->searchParams),
        ];
    }

    /**
     * @param array{
     *   entityType: string,
     *   ids: ?string[],
     *   searchParams: string,
     * } $data
     */
    public function __unserialize(array $data): void
    {
        $this->entityType = $data['entityType'];
        $this->ids = $data['ids'];
        $this->searchParams = unserialize($data['searchParams']);
    }
}
