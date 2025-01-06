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

use Espo\Core\Utils\Config;
use RuntimeException;

class ConfigDataProvider
{
    public function __construct(private Config $config)
    {}

    /**
     * Get decimal places.
     *
     * @since 8.3.0
     */
    public function getDecimalPlaces(): ?int
    {
        return $this->config->get('currencyDecimalPlaces');
    }

    /**
     * Get a system default currency.
     */
    public function getDefaultCurrency(): string
    {
        return $this->config->get('defaultCurrency');
    }

    /**
     * Get a base currency, used for conversion.
     */
    public function getBaseCurrency(): string
    {
        return $this->config->get('baseCurrency');
    }

    /**
     * Get a list of available currencies.
     *
     * @return array<int, string>
     */
    public function getCurrencyList(): array
    {
        return $this->config->get('currencyList') ?? [];
    }

    /**
     * Whether a currency is available in the system.
     */
    public function hasCurrency(string $currencyCode): bool
    {
        return in_array($currencyCode, $this->getCurrencyList());
    }

    /**
     * Get a rate of a specific currency related to the base currency.
     */
    public function getCurrencyRate(string $currencyCode): float
    {
        $rates = $this->config->get('currencyRates') ?? [];

        if (!$this->hasCurrency($currencyCode)) {
            throw new RuntimeException("Can't get currency rate of '{$currencyCode}' currency.");
        }

        return $rates[$currencyCode] ?? 1.0;
    }

    /**
     * Get rates.
     */
    public function getCurrencyRates(): Rates
    {
        $rates = $this->config->get('currencyRates') ?? [];

        $rates[$this->getBaseCurrency()] = 1.0;

        return Rates::fromAssoc($rates, $this->getBaseCurrency());
    }
}
