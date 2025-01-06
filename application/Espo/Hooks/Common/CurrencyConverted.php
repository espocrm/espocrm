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

namespace Espo\Hooks\Common;

use Espo\Core\ORM\Type\FieldType;
use Espo\ORM\Entity;
use Espo\Core\Di;

class CurrencyConverted implements Di\MetadataAware, Di\ConfigAware
{
    use Di\MetadataSetter;
    use Di\ConfigSetter;

    public static int $order = 1;

    public function beforeSave(Entity $entity): void
    {
        $fieldDefs = $this->metadata->get(['entityDefs', $entity->getEntityType(), 'fields'], []);

        foreach ($fieldDefs as $fieldName => $defs) {
            if (empty($defs['type']) || $defs['type'] !== FieldType::CURRENCY_CONVERTED) {
                continue;
            }

            $currencyFieldName = substr($fieldName, 0, -9);

            $currencyCurrencyFieldName = $currencyFieldName . 'Currency';

            if (
                !$entity->isAttributeChanged($currencyFieldName) &&
                !$entity->isAttributeChanged($currencyCurrencyFieldName)
            ) {
                continue;
            }

            if (empty($fieldDefs[$currencyFieldName])) {
                continue;
            }

            if ($entity->get($currencyFieldName) === null) {
                $entity->set($fieldName, null);

                continue;
            }

            $currency = $entity->get($currencyCurrencyFieldName);
            $value = $entity->get($currencyFieldName);

            if (!$currency) {
                continue;
            }

            $rates = $this->config->get('currencyRates', []);
            $baseCurrency = $this->config->get('baseCurrency');
            $defaultCurrency = $this->config->get('defaultCurrency');

            if ($defaultCurrency === $currency) {
                $targetValue = $value;
            } else {
                $targetValue = $value;
                $targetValue = $targetValue / ($rates[$baseCurrency] ?? 1.0);
                $targetValue = $targetValue * ($rates[$currency] ?? 1.0);

                $targetValue = round($targetValue, 2);
            }

            $entity->set($fieldName, $targetValue);
        }
    }
}
