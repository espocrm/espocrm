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

namespace Espo\Core\Record;

use Espo\ORM\Collection as OrmCollection;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;

use stdClass;

/**
 * Contains an ORM collection and total number of records.
 *
 * @template-covariant TEntity of Entity
 */
class Collection
{
    public const TOTAL_HAS_MORE = -1;
    public const TOTAL_HAS_NO_MORE = -2;

    /**
     * @param OrmCollection<TEntity> $collection
     */
    public function __construct(
        private OrmCollection $collection,
        private ?int $total = null
    ) {}

    /**
     * Get a total number of records in DB (that matches applied search parameters).
     */
    public function getTotal(): ?int
    {
        return $this->total;
    }

    /**
     * Get an ORM collection.
     *
     * @return OrmCollection<TEntity>
     */
    public function getCollection(): OrmCollection
    {
        return $this->collection;
    }

    /**
     * Get a value map list.
     *
     * @return stdClass[]
     */
    public function getValueMapList(): array
    {
        if (
            $this->collection instanceof EntityCollection &&
            !$this->collection->getEntityType()
        ) {
            $list = [];

            foreach ($this->collection as $e) {
                $item = $e->getValueMap();

                $item->_scope = $e->getEntityType();

                $list[] = $item;
            }

            return $list;
        }

        return $this->collection->getValueMapList();
    }

    /**
     * Create.
     *
     * @template CEntity of Entity
     * @param OrmCollection<CEntity> $collection
     * @return self<CEntity>
     */
    public static function create(OrmCollection $collection, ?int $total = null): self
    {
        return new self($collection, $total);
    }

    /**
     * Create w/o count.
     *
     * @template CEntity of Entity
     * @param OrmCollection<CEntity> $collection
     * @return self<CEntity>
     */
    public static function createNoCount(OrmCollection $collection, ?int $maxSize): self
    {
        if (
            $maxSize !== null &&
            $collection instanceof EntityCollection &&
            count($collection) > $maxSize
        ) {
            $copyCollection = new EntityCollection([...$collection], $collection->getEntityType());

            unset($copyCollection[count($copyCollection) - 1]);

            return new self($copyCollection, self::TOTAL_HAS_MORE);
        }

        return new self($collection, self::TOTAL_HAS_NO_MORE);
    }

    /**
     * To API output. To be used in API actions.
     *
     * @since 9.1.0
     */
    public function toApiOutput(): stdClass
    {
        return (object) [
            'total' => $this->getTotal(),
            'list' => $this->getValueMapList(),
        ];
    }
}
