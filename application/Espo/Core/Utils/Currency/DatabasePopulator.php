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

namespace Espo\Core\Utils\Currency;

use Espo\Entities\Currency;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\ORM\Name\Attribute;

/**
 * Populates currency rates into database.
 */
class DatabasePopulator
{
    public function __construct(
        private Config $config,
        private EntityManager $entityManager)
    {}

    public function process(): void
    {
        $defaultCurrency = $this->config->get('defaultCurrency');
        $baseCurrency = $this->config->get('baseCurrency');
        $currencyRates = $this->config->get('currencyRates');

        if ($defaultCurrency !== $baseCurrency) {
            $currencyRates = $this->exchangeRates($baseCurrency, $defaultCurrency, $currencyRates);
        }

        $currencyRates[$defaultCurrency] = 1.00;

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from(Currency::ENTITY_TYPE)
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        foreach ($currencyRates as $currencyName => $rate) {
            $this->entityManager->createEntity(Currency::ENTITY_TYPE, [
                Attribute::ID => $currencyName,
                'rate' => $rate,
            ]);
        }
    }

    /**
     * @param array<string, float> $currencyRates
     * @return array<string, float>
     */
    private function exchangeRates(string $baseCurrency, string $defaultCurrency, array $currencyRates): array
    {
        $precision = 5;
        $defaultCurrencyRate = round(1 / $currencyRates[$defaultCurrency], $precision);

        $exchangedRates = [];
        $exchangedRates[$baseCurrency] = $defaultCurrencyRate;

        unset($currencyRates[$baseCurrency], $currencyRates[$defaultCurrency]);

        foreach ($currencyRates as $currencyName => $rate) {
            $exchangedRates[$currencyName] = round($rate * $defaultCurrencyRate, $precision);
        }

        return $exchangedRates;
    }
}
