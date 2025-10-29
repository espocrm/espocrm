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

namespace Espo\Core\Utils\Currency;

use Espo\Core\Currency\ConfigDataProvider;
use Espo\Entities\Currency;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;

/**
 * Populates currency rates into database.
 */
class DatabasePopulator
{
    private const PRECISION = 5;

    public function __construct(
        private EntityManager $entityManager,
        private ConfigDataProvider $configDataProvider,
    ) {}

    public function process(): void
    {
        $defaultCurrency = $this->configDataProvider->getDefaultCurrency();
        $baseCurrency = $this->configDataProvider->getBaseCurrency();
        $currencyRates = $this->configDataProvider->getCurrencyRates()->toAssoc();

        if ($defaultCurrency !== $baseCurrency) {
            $currencyRates = $this->exchangeRates($baseCurrency, $defaultCurrency, $currencyRates);
        }

        $currencyRates[$defaultCurrency] = 1.00;

        $this->entityManager->getTransactionManager()->start();

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from(Currency::ENTITY_TYPE)
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        foreach ($currencyRates as $currencyName => $rate) {
            $this->entityManager->createEntity(Currency::ENTITY_TYPE, [
                Attribute::ID => $currencyName,
                Currency::FIELD_RATE => $rate,
            ]);
        }

        $this->entityManager->getTransactionManager()->commit();
    }

    /**
     * @param array<string, float> $currencyRates
     * @return array<string, float>
     */
    private function exchangeRates(string $baseCurrency, string $defaultCurrency, array $currencyRates): array
    {
        $defaultCurrencyRate = round(1 / $currencyRates[$defaultCurrency], self::PRECISION);

        $exchangedRates = [];
        $exchangedRates[$baseCurrency] = $defaultCurrencyRate;

        unset($currencyRates[$baseCurrency], $currencyRates[$defaultCurrency]);

        foreach ($currencyRates as $currencyName => $rate) {
            $exchangedRates[$currencyName] = round($rate * $defaultCurrencyRate, self::PRECISION);
        }

        return $exchangedRates;
    }
}
