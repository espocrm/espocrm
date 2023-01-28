<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\Currency;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Acl;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Currency\DatabasePopulator;
use stdClass;

class RateService
{
    private const SCOPE = 'Currency';

    public function __construct(
        private Config $config,
        private ConfigWriter $configWriter,
        private Acl $acl,
        private DatabasePopulator $databasePopulator
    ) {}

    /**
     * @throws Forbidden
     */
    public function get(): stdClass
    {
        if (!$this->acl->check(self::SCOPE)) {
            throw new Forbidden();
        }

        if ($this->acl->getLevel(self::SCOPE, Acl\Table::ACTION_READ) !== Acl\Table::LEVEL_YES) {
            throw new Forbidden();
        }

        return (object) (
            $this->config->get('currencyRates') ?? []
        );
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function set(stdClass $rates): stdClass
    {
        if (!$this->acl->check(self::SCOPE)) {
            throw new Forbidden();
        }

        if ($this->acl->getLevel(self::SCOPE, Acl\Table::ACTION_EDIT) !== Acl\Table::LEVEL_YES) {
            throw new Forbidden();
        }

        $config = $this->config;

        $currencyList = $config->get('currencyList') ?? [];

        foreach (get_object_vars($rates) as $key => $value) {
            if (!is_string($key) || !in_array($key, $currencyList)) {
                unset($rates->$key);

                continue;
            }

            if (!is_numeric($value) || is_string($value)) {
                throw new BadRequest();
            }

            if ($value < 0) {
                throw new BadRequest();
            }
        }

        foreach ($currencyList as $currency) {
            if ($currency == $config->get('baseCurrency')) {
                continue;
            }

            if (!isset($rates->$currency)) {
                $rates->$currency = $config->get('currencyRates.' . $currency) ?? 1.0;
            }
        }

        $this->configWriter->set('currencyRates', $rates);
        $this->configWriter->save();

        $this->databasePopulator->process();

        return (object) ($config->get('currencyRates') ?? []);
    }
}
