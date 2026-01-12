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

namespace Espo\Tools\Currency;

use Espo\Core\Currency\ConfigDataProvider;
use Espo\Core\Currency\InternalRateEntryProvider;
use Espo\Core\Field\Date;
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
class RateEntryProvider
{
    /** @var WeakMap<CurrencyRecord, ?CurrencyRecordRate> */
    private WeakMap $map;

    public function __construct(
        private ConfigDataProvider $configDataProvider,
        private EntityManager $entityManager,
        private DateTime $dateTime,
        private InternalRateEntryProvider $internalRateEntryProvider,
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
     * @throws NotEnabled
     */
    public function prepareNew(string $code, Date $date): CurrencyRecordRate
    {
        $record = $this->getRecordByCode($code);

        $entry = $this->entityManager->getRDBRepositoryByClass(CurrencyRecordRate::class)->getNew();

        $entry
            ->setRecord($record)
            ->setDate($date);

        return $entry;
    }

    private function getRateEntryForRecord(CurrencyRecord $record, ?Date $date = null): ?CurrencyRecordRate
    {
        $date ??= $this->dateTime->getToday();
        $base = $this->configDataProvider->getBaseCurrency();

        return $this->internalRateEntryProvider->getRateEntryForRecord($record, $date, $base);
    }

    /**
     * Get rate against the base currency by a record.
     *
     * @return ?numeric-string
     */
    public function getRateForRecord(CurrencyRecord $record, ?Date $date = null): ?string
    {
        $rateEntry = $this->getRateEntryForRecord($record, $date);

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
        $record = $this->getRecordByCode($code);

        return $this->getRateForRecord($record);
    }

    /**
     * @throws NotEnabled
     */
    public function getRateEntryOnDate(string $code, Date $date): ?CurrencyRecordRate
    {
        $record = $this->getRecordByCode($code);

        return $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecordRate::class)
            ->where([
                CurrencyRecordRate::ATTR_RECORD_ID => $record->getId(),
                CurrencyRecordRate::FIELD_BASE_CODE => $this->configDataProvider->getBaseCurrency(),
                CurrencyRecordRate::FIELD_DATE  => $date->toString(),
            ])
            ->order(CurrencyRecordRate::FIELD_DATE, Order::DESC)
            ->findOne();
    }

    /**
     * @since 9.3.0
     * @throws NotEnabled
     * @noinspection PhpUnused
     */
    public function getRateEntryOnAsOfDate(string $code, Date $date): ?CurrencyRecordRate
    {
        $record = $this->getRecordByCode($code);

        return $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecordRate::class)
            ->where([
                CurrencyRecordRate::ATTR_RECORD_ID => $record->getId(),
                CurrencyRecordRate::FIELD_BASE_CODE => $this->configDataProvider->getBaseCurrency(),
                CurrencyRecordRate::FIELD_DATE . '<=' => $date->toString(),
            ])
            ->order(CurrencyRecordRate::FIELD_DATE, Order::DESC)
            ->findOne();
    }

    /**
     * @throws NotEnabled
     */
    private function getRecordByCode(string $code): CurrencyRecord
    {
        $record = $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecord::class)
            ->where([
                CurrencyRecord::FIELD_CODE => $code,
                CurrencyRecord::FIELD_STATUS => CurrencyRecord::STATUS_ACTIVE,
            ])
            ->findOne();

        if (!$record) {
            throw new NotEnabled("Currency $code is not enabled.");
        }

        return $record;
    }
}
