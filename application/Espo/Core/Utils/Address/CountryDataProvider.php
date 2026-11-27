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

namespace Espo\Core\Utils\Address;

use Espo\Core\Name\Field;
use Espo\Core\Utils\Cache\DataCacheAccess;
use Espo\Entities\AddressCountry;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Order;

class CountryDataProvider
{
    private const string CACHE_KEY = 'addressCountryData';
    private const int LIMIT = 1000;

    /**
     * @param DataCacheAccess<array{list: string[], preferredList: string[]}> $dataCacheAccess
     */
    public function __construct(
        private EntityManager $entityManager,
        private DataCacheAccess $dataCacheAccess,
    ) {
        $dataCacheAccess->init(
            key: self::CACHE_KEY,
            loader: function () {
                return $this->load();
            },
        );
    }

    /**
     * @return array{list: string[], preferredList: string[]}
     */
    public function get(): array
    {
        return $this->dataCacheAccess->get();
    }

    /**
     * @return array{list: string[], preferredList: string[]}
     */
    private function load(): array
    {
        $list = [];
        $preferredList = [];

        /** @var iterable<AddressCountry> $collection */
        $collection = $this->entityManager
            ->getRDBRepositoryByClass(AddressCountry::class)
            ->sth()
            ->select([
                Field::NAME,
                AddressCountry::FIELD_IS_PREFERRED,
            ])
            ->order(Field::NAME, Order::ASC)
            ->limit(0, self::LIMIT)
            ->find();

        foreach ($collection as $entity) {
            $list[] = $entity->getName();

            if ($entity->isPreferred()) {
                $preferredList[] = $entity->getName();
            }
        }

        return [
            'list' => $list,
            'preferredList' => $preferredList,
        ];
    }
}
