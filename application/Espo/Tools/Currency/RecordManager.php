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
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Entities\CurrencyRecord;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\UpdateBuilder;
use Espo\Tools\Currency\Exceptions\NotEnabled;

class RecordManager
{
    public function __construct(
        private ConfigDataProvider $configDataProvider,
        private EntityManager $entityManager,
        private ConfigWriter $configWriter,
        private CurrencyRatesProvider $currencyRatesProvider,
    ) {}

    public function sync(): void
    {
        $this->entityManager->getTransactionManager()->run(function () {
            $this->syncInTransaction();
        });

        $this->syncToConfig();
    }

    private function syncInTransaction(): void
    {
        $this->lock();

        foreach ($this->configDataProvider->getCurrencyList() as $code) {
            $this->syncCode($code);
        }

        $this->deactivateNotListed();
    }

    private function syncCode(string $code): void
    {
        $record = $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecord::class)
            ->where([CurrencyRecord::FIELD_CODE => $code])
            ->findOne();

        if (!$record) {
            $record = $this->entityManager->getRDBRepositoryByClass(CurrencyRecord::class)->getNew();

            $record->setCode($code);
        }

        $record->setStatus(CurrencyRecord::STATUS_ACTIVE);

        $this->entityManager->saveEntity($record);
    }

    private function deactivateNotListed(): void
    {
        $list = $this->configDataProvider->getCurrencyList();

        $updateQuery = UpdateBuilder::create()
            ->in(CurrencyRecord::ENTITY_TYPE)
            ->set([
                CurrencyRecord::FIELD_STATUS => CurrencyRecord::STATUS_INACTIVE,
            ])
            ->where([
                CurrencyRecord::FIELD_CODE . '!=' => $list,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($updateQuery);
    }

    private function lock(): void
    {
        $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecord::class)
            ->forUpdate()
            ->sth()
            ->find();
    }

    public function syncToConfig(): void
    {
        $rates = [];

        foreach ($this->configDataProvider->getCurrencyList() as $code) {
            try {
                $rate = $this->currencyRatesProvider->getRate($code) ?? '1.0';
            } catch (Exceptions\NotEnabled) {
                continue;
            }

            $rates[$code] = (float) $rate;
        }

        $this->configWriter->set('currencyRates', $rates);
        $this->configWriter->save();
    }

    /**
     * @throws NotEnabled
     */
    public function syncCodeToConfig(string $code): void
    {
        $rates = $this->configDataProvider->getCurrencyRates()->toAssoc();

        $rate = $this->currencyRatesProvider->getRate($code) ?? '1.0';

        $rates[$code] = (float) $rate;

        $this->configWriter->set('currencyRates', $rates);
        $this->configWriter->save();
    }
}
