<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\BadRequest;

class CurrencyRate extends \Espo\Core\Services\Base
{
    protected function init()
    {
        $this->addDependency('config');
        $this->addDependency('dataManager');
    }

    public function get() : object
    {
        return (object) ($this->getInjection('config')->get('currencyRates') ?? []);
    }

    public function set(object $rates) : object
    {
        $config = $this->getInjection('config');
        $currencyList = $config->get('currencyList') ?? [];

        foreach (get_object_vars($rates) as $key => $value) {
            if (!is_string($key) || !in_array($key, $currencyList)) {
                unset($rates->$key);
                continue;
            }
            if (!is_numeric($value) || is_string($value)) throw new BadRequest();
            if ($value < 0) throw new BadRequest();
        }

        foreach ($currencyList as $currency) {
            if ($currency == $config->get('baseCurrency')) continue;

            if (!isset($rates->$currency)) {
                $rates->$currency = $config->get('currencyRates.' . $currency) ?? 1.0;
            }
        }

        $data = (object) ['currencyRates' => $rates];

        $config->setData($data);
        $config->save();

        $this->getInjection('dataManager')->rebuildDatabase([]);

        return (object) ($config->get('currencyRates') ?? []);
    }
}
