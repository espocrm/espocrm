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

namespace Espo\Core\Record;

use Espo\ORM\Collection as OrmCollection;
use Espo\ORM\EntityCollection;

use stdClass;

/**
 * Contains an an ORM collection and total number of records.
 */
class Collection
{
    public const TOTAL_HAS_MORE = -1;

    public const TOTAL_HAS_NO_MORE = -2;

    private $collection;

    private $total;

    public function __construct(OrmCollection $collection, ?int $total = null)
    {
        $this->collection = $collection;
        $this->total = $total;
    }

    /**
     * Get a total number of records in DB (that matches applied search parameters).
     */
    public function getTotal(): ?int
    {
        return $this->total;
    }

    /**
     * Get an ORM collection.
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
}
