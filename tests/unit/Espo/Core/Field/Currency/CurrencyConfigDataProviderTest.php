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

namespace tests\unit\Espo\Core\Field\Currency;

use Espo\Core\Currency\ConfigDataProvider as CurrencyConfigDataProvider;
use Espo\Core\Utils\Config;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CurrencyConfigDataProviderTest extends TestCase
{
    private $config;
    private $provider;

    protected function setUp() : void
    {
        $this->config = $this->createMock(Config::class);

        $this->provider = new CurrencyConfigDataProvider($this->config);
    }

    public function testDefaultCurrency()
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('defaultCurrency')
            ->willReturn('USD');

        $currency = $this->provider->getDefaultCurrency();

        $this->assertEquals('USD', $currency);
    }

    public function testBaseCurrency()
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('baseCurrency')
            ->willReturn('USD');

        $currency = $this->provider->getBaseCurrency();

        $this->assertEquals('USD', $currency);
    }

    public function testCurrencyList()
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('currencyList')
            ->willReturn(['USD', 'EUR']);

        $result = $this->provider->getCurrencyList();

        $this->assertEquals(['USD', 'EUR'], $result);
    }

    public function testHasCurrency()
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('currencyList')
            ->willReturn(['USD', 'EUR']);

        $result = $this->provider->hasCurrency('EUR');

        $this->assertTrue($result);
    }

    public function testCurrencyRate1()
    {
        $invokedCount = $this->exactly(2);

        $this->config
            ->expects($invokedCount)
            ->method('get')
            ->willReturnCallback(function ($param) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals('currencyRates', $param);

                    return [
                        'EUR' => 1.2,
                    ];
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals('currencyList', $param);

                    return ['USD', 'EUR'];
                }

                throw new RuntimeException();
            });

        $result = $this->provider->getCurrencyRate('EUR');

        $this->assertEquals(1.2, $result);
    }

    public function testCurrencyRate2()
    {
        $invokedCount = $this->exactly(2);

        $this->config
            ->expects($invokedCount)
            ->method('get')
            ->willReturnCallback(function ($param) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals('currencyRates', $param);

                    return [
                        'EUR' => 1.2,
                    ];
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals('currencyList', $param);

                    return ['USD', 'EUR'];
                }

                throw new RuntimeException();
            });

        $result = $this->provider->getCurrencyRate('USD');

        $this->assertEquals(1.0, $result);
    }
}
