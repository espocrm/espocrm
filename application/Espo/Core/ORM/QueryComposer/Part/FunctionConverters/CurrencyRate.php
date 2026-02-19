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

namespace Espo\Core\ORM\QueryComposer\Part\FunctionConverters;

use Espo\Core\Currency\ConfigDataProvider;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\QueryComposer\Part\FunctionConverter;
use Espo\ORM\QueryComposer\Util;
use RuntimeException;

/**
 * @noinspection PhpUnused
 */
class CurrencyRate implements FunctionConverter
{
    private const int PRECISION = 5;

    public function __construct(
        private ConfigDataProvider $config,
    ) {}

    public function convert(string ...$argumentList): string
    {
        $arg = $argumentList[0] ?? null;

        if (!is_string($arg) || !Util::isArgumentString($arg)) {
            throw new RuntimeException("CURRENCY_RATE function accepts only literal string argument.");
        }

        $code = substr($arg, 1, -1);

        if (!in_array($code, $this->config->getCurrencyList())) {
            return Expression::value(0)->getValue();
        }

        $baseCurrency = $this->config->getBaseCurrency();
        $defaultCurrency = $this->config->getDefaultCurrency();

        $rates = $this->config->getCurrencyRates()->toAssoc();

        if ($defaultCurrency !== $baseCurrency) {
            $rates = $this->exchangeRates($baseCurrency, $defaultCurrency, $rates);
        }

        $rate = $rates[$code] ?? 1.0;

        return Expression::value($rate)->getValue();
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

        foreach ($currencyRates as $code => $rate) {
            $exchangedRates[$code] = round($rate * $defaultCurrencyRate, self::PRECISION);
        }

        return $exchangedRates;
    }
}
