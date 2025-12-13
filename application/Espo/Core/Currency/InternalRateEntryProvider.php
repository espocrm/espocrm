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

namespace Espo\Core\Currency;

use Espo\Core\Field\Date;
use Espo\Core\ORM\EntityManagerProxy;
use Espo\Entities\CurrencyRecord;
use Espo\Entities\CurrencyRecordRate;
use Espo\ORM\Query\Part\Order;
use Traversable;

/**
 * @internal
 */
class InternalRateEntryProvider
{
    public function __construct(
        private EntityManagerProxy $entityManager,
    ) {}

    /**
     * @return CurrencyRecordRate[]
     */
    public function getRateEntries(Date $date, string $base): array
    {
        $rates = [];

        foreach ($this->getActiveCurrencyRecords() as $record) {
            $rate = $this->getRateEntryForRecord($record, $date, $base);

            if ($rate) {
                $rates[] = $rate;
            }
        }

        return $rates;
    }

    /**
     * @return Traversable<int, CurrencyRecord>
     */
    private function getActiveCurrencyRecords(): Traversable
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecord::class)
            ->where([
                CurrencyRecord::FIELD_STATUS => CurrencyRecord::STATUS_ACTIVE,
            ])
            ->find();
    }

    public function getRateEntryForRecord(CurrencyRecord $record, Date $date, string $base): ?CurrencyRecordRate
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecordRate::class)
            ->where([
                CurrencyRecordRate::ATTR_RECORD_ID => $record->getId(),
                CurrencyRecordRate::FIELD_BASE_CODE => $base,
                CurrencyRecordRate::FIELD_DATE . '<=' => $date->toString(),
            ])
            ->order(CurrencyRecordRate::FIELD_DATE, Order::DESC)
            ->findOne();
    }
}
