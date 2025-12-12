<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Tools\Currency;

use Espo\Core\Currency\ConfigDataProvider;
use Espo\Core\Utils\DateTime;
use Espo\Entities\CurrencyRecord;
use Espo\Entities\CurrencyRecordRate;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Order;
use Espo\Tools\Currency\Exceptions\NotEnabled;
use WeakMap;

/**
 * @since 9.3.0
 */
class CurrencyRatesProvider
{
    /** @var WeakMap<CurrencyRecord, ?CurrencyRecordRate> */
    private WeakMap $map;

    public function __construct(
        private ConfigDataProvider $configDataProvider,
        private EntityManager $entityManager,
        private DateTime $dateTime,
    ) {
        $this->map = new WeakMap();
    }

    public function getCurrentRateEntry(CurrencyRecord $record): ?CurrencyRecordRate
    {
        if (!$this->map->offsetExists($record)) {
            $this->map[$record] = $this->entityManager
                ->getRDBRepositoryByClass(CurrencyRecordRate::class)
                ->where([
                    CurrencyRecordRate::ATTR_RECORD_ID => $record->getId(),
                    CurrencyRecordRate::FIELD_BASE_CODE => $this->configDataProvider->getBaseCurrency(),
                    CurrencyRecordRate::FIELD_DATE . '<=' => $this->dateTime->getToday()->toString(),
                ])
                ->order(CurrencyRecordRate::FIELD_DATE, Order::DESC)
                ->findOne();
        }

        return $this->map[$record];
    }

    /**
     * Get rate against the base currency by a record.
     *
     * @return ?numeric-string
     */
    public function getRateByRecord(CurrencyRecord $record): ?string
    {
        $rateEntry = $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecordRate::class)
            ->where([
                CurrencyRecordRate::ATTR_RECORD_ID => $record->getId(),
                CurrencyRecordRate::FIELD_BASE_CODE => $this->configDataProvider->getBaseCurrency(),
                CurrencyRecordRate::FIELD_DATE . '<=' => $this->dateTime->getToday()->toString(),
            ])
            ->order(CurrencyRecordRate::FIELD_DATE, Order::DESC)
            ->findOne();

        return $rateEntry?->getRate();
    }

    /**
     * Get rate against the base currency.
     *
     * @param string $code
     * @return ?numeric-string
     * @throws NotEnabled
     */
    public function getRate(string $code): ?string
    {
        $record = $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecord::class)
            ->where([CurrencyRecord::FIELD_CODE => $code])
            ->findOne();

        if (!$record) {
            throw new NotEnabled("Currency $code is not enabled.");
        }

        return $this->getRateByRecord($record);
    }
}
