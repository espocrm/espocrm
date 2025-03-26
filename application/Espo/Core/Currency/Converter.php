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

namespace Espo\Core\Currency;

use Espo\Core\Field\Currency;

use RuntimeException;

/**
 * Converts currency values.
 */
class Converter
{
    private ConfigDataProvider $configDataProvider;

    public function __construct(ConfigDataProvider $configDataProvider)
    {
        $this->configDataProvider = $configDataProvider;
    }

    /**
     * Convert a currency value to a specific currency.
     *
     * @throws RuntimeException
     */
    public function convert(Currency $value, string $targetCurrencyCode): Currency
    {
        if (!$this->configDataProvider->hasCurrency($targetCurrencyCode)) {
            throw new RuntimeException("Can't convert currency to unknown currency '{$targetCurrencyCode}.");
        }

        $rate = $this->configDataProvider->getCurrencyRate($value->getCode());
        $targetRate = $this->configDataProvider->getCurrencyRate($targetCurrencyCode);

        $convertedAmount = $this->convertAmount($value->getAmountAsString(), $rate, $targetRate);

        return new Currency($convertedAmount, $targetCurrencyCode);
    }

    /**
     * Convert a currency value to a specific currency with specific rates.
     * Base currency should have rate equal to `1.0`.
     *
     * @throws RuntimeException
     */
    public function convertWithRates(Currency $value, string $targetCurrencyCode, Rates $rates): Currency
    {
        $currencyCode = $value->getCode();

        if (!$rates->hasRate($currencyCode)) {
            throw new RuntimeException("No rate for the currency '{$currencyCode}.");
        }

        if (!$rates->hasRate($targetCurrencyCode)) {
            throw new RuntimeException("No rate for the currency '{$targetCurrencyCode}.");
        }

        $rate = $rates->getRate($currencyCode);
        $targetRate = $rates->getRate($targetCurrencyCode);

        $convertedAmount = $this->convertAmount($value->getAmountAsString(), $rate, $targetRate);

        return new Currency($convertedAmount, $targetCurrencyCode);
    }

    /**
     * Convert a currency value to the system default currency.
     */
    public function convertToDefault(Currency $value): Currency
    {
        $targetCurrencyCode = $this->configDataProvider->getDefaultCurrency();

        return $this->convert($value, $targetCurrencyCode);
    }

    /**
     * @param numeric-string $amount
     * @return numeric-string
     */
    private function convertAmount(string $amount, float $rate, float $targetRate): string
    {
        return CalculatorUtil::divide(
            CalculatorUtil::multiply($amount, (string) $rate),
            (string) $targetRate
        );
    }
}
