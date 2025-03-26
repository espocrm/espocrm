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

namespace Espo\Classes\FieldValidators;

use Espo\Core\Field\Currency;
use Espo\Core\Utils\Config;
use Espo\ORM\BaseEntity;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Entity;

class CurrencyType extends FloatType
{
    private const DEFAULT_PRECISION = 13;

    public function __construct(private Config $config) {}

    protected function isNotEmpty(Entity $entity, string $field): bool
    {
        return
            $entity->has($field) && $entity->get($field) !== null &&
            $entity->has($field . 'Currency') && $entity->get($field . 'Currency') !== null &&
            $entity->get($field . 'Currency') !== '';
    }

    public function checkValid(Entity $entity, string $field): bool
    {
        if (!$this->isNotEmpty($entity, $field)) {
            return true;
        }

        if ($entity->getAttributeType($field) !== Entity::VARCHAR) {
            return true;
        }

        /** @var string $value */
        $value = $entity->get($field);

        if (preg_match('/^-?[0-9]+\.?[0-9]*$/', $value)) {
            return true;
        }

        return false;
    }

    public function checkInPermittedRange(Entity $entity, string $field): bool
    {
        if (!$this->isNotEmpty($entity, $field)) {
            return true;
        }

        if ($entity->getAttributeType($field) !== Entity::VARCHAR) {
            return true;
        }

        if (!$entity instanceof BaseEntity) {
            return true;
        }

        /** @var int $precision */
        $precision = $entity->getAttributeParam($field, AttributeParam::PRECISION) ?? self::DEFAULT_PRECISION;

        $value = $entity->get($field);

        $currency = Currency::create($value, 'USD');

        if ($currency->isNegative()) {
            $currency = $currency->multiply(-1);
        }

        $pad = str_pad('', $precision, '9');

        assert(is_numeric($pad));

        $limit = Currency::create($pad, 'USD');

        if ($currency->compare($limit) === 1) {
            return false;
        }

        return true;
    }

    public function checkValidCurrency(Entity $entity, string $field): bool
    {
        $attribute = $field . 'Currency';

        if (!$entity->has($attribute)) {
            return true;
        }

        $currency = $entity->get($attribute);
        $currencyList = $this->config->get('currencyList') ?? [$this->config->get('defaultCurrency')];

        if (
            $currency === null &&
            !$entity->has($field) &&
            $entity->isNew()
        ) {
            return true;
        }

        if (
            $currency === null &&
            $entity->has($field) &&
            $entity->get($field) === null
        ) {
            return true;
        }

        return in_array($currency, $currencyList);
    }
}
