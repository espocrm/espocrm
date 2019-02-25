<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils\Database\Schema\rebuildActions;

class Currency extends \Espo\Core\Utils\Database\Schema\BaseRebuildActions
{

    public function afterRebuild()
    {
        $defaultCurrency = $this->getConfig()->get('defaultCurrency');

        $baseCurrency = $this->getConfig()->get('baseCurrency');
        $currencyRates = $this->getConfig()->get('currencyRates');

        if ($defaultCurrency != $baseCurrency) {
            $currencyRates = $this->exchangeRates($baseCurrency, $defaultCurrency, $currencyRates);
        }

        $currencyRates[$defaultCurrency] = '1.00';

        $pdo = $this->getEntityManager()->getPDO();

        $sql = "TRUNCATE `currency`";
        $pdo->prepare($sql)->execute();

        foreach ($currencyRates as $currencyName => $rate) {

            $sql = "
                INSERT INTO `currency`
                (id, rate)
                VALUES
                (".$pdo->quote($currencyName) . ", " . $pdo->quote($rate) . ")
            ";
            $pdo->prepare($sql)->execute();
        }
    }

    /**
     * Calculate exchange rates if defaultCurrency doesn't equals baseCurrency
     *
     * @param  string $baseCurrency
     * @param  string $defaultCurrency
     * @param  array $currencyRates   [description]
     * @return array  - List of new currency rates
     */
    protected function exchangeRates($baseCurrency, $defaultCurrency, array $currencyRates)
    {
        $precision = 5;
        $defaultCurrencyRate = round(1 / $currencyRates[$defaultCurrency], $precision);

        $exchangedRates = array();
        $exchangedRates[$baseCurrency] = $defaultCurrencyRate;

        unset($currencyRates[$baseCurrency], $currencyRates[$defaultCurrency]);

        foreach ($currencyRates as $currencyName => $rate) {
            $exchangedRates[$currencyName] = round($rate * $defaultCurrencyRate, $precision);
        }

        return $exchangedRates;
    }

}

