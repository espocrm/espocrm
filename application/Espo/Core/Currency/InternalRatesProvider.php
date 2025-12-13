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
use Espo\Core\Utils\Config\SystemConfig;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\DateTime;
use LogicException;
use stdClass;

/**
 * @internal
 */
class InternalRatesProvider
{
    private string $cacheKey = 'currencyRates';

    /** @var (stdClass&object{date: string, rates: stdClass})|null */
    private ?stdClass $data = null;

    public function __construct(
        private DataCache $dataCache,
        private SystemConfig $systemConfig,
        private DateTime $dateTime,
        private InternalRateEntryProvider $rateEntryProvider,
    ) {}

    /**
     * @return array<string, float>
     */
    public function get(string $base): array
    {
        $this->data ??= $this->getCachedData();

        $today = $this->dateTime->getToday();

        if (!$this->data || $this->data->date !== $today->toString()) {
            $this->data = $this->buildData($today, $base);

            $this->storeData();
        }

        if ($this->data === null) {
            throw new LogicException();
        }

        return get_object_vars($this->data->rates);
    }

    /**
     * @return (stdClass&object{date: string, rates: stdClass})|null
     */
    private function getCachedData(): ?stdClass
    {
        if (!$this->systemConfig->useCache()) {
            return null;
        }

        $cached = $this->dataCache->tryGet($this->cacheKey);

        if (!$cached instanceof stdClass) {
            return null;
        }

        if (!isset($cached->date) || !isset($cached->rates)) {
            $this->dataCache->clear($this->cacheKey);

            return null;
        }

        /** @var stdClass&object{date: string, rates: stdClass} */
        return $cached;
    }

    private function storeData(): void
    {
        if (!$this->systemConfig->useCache()) {
            return;
        }

        if (!$this->data instanceof stdClass) {
            throw new LogicException();
        }

        $this->dataCache->store($this->cacheKey, $this->data);
    }

    /**
     * @return stdClass&object{date: string, rates: stdClass}
     */
    private function buildData(Date $today, string $base): stdClass
    {
        $rates = [];

        foreach ($this->rateEntryProvider->getRateEntries($today, $base) as $rate) {
            $rates[$rate->getRecord()->getCode()] = (float) $rate->getRate();
        }

        return (object) [
            'date' => $today->toString(),
            'rates' => (object) $rates,
        ];
    }
}
