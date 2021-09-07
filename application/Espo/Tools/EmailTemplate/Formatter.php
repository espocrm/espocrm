<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\EmailTemplate;

use Espo\ORM\Entity;

use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\NumberUtil;
use Espo\Core\Utils\Language;

class Formatter
{
    private $metadata;

    private $config;

    private $dateTime;

    private $number;

    private $language;

    public function __construct(
        Metadata $metadata,
        Config $config,
        DateTimeUtil $dateTime,
        NumberUtil $number,
        Language $language
    ) {
        $this->metadata = $metadata;
        $this->config = $config;
        $this->dateTime = $dateTime;
        $this->number = $number;
        $this->language = $language;
    }

    public function formatAttributeValue(Entity $entity, string $attribute): ?string
    {
        $value = $entity->get($attribute);

        $fieldType = $this->metadata
            ->get(['entityDefs', $entity->getEntityType(), 'fields', $attribute, 'type']);

        $attributeType = $entity->getAttributeType($attribute);

        if ($fieldType === 'enum') {
            if ($value === null) {
                return '';
            }

            return (string) $this->language->translateOption($value, $attribute, $entity->getEntityType());
        }

        if ($fieldType === 'array' || $fieldType === 'multiEnum' || $fieldType === 'checklist') {
            $valueList = [];

            if (!is_array($value)) {
                return '';
            }

            foreach ($value as $v) {
                $valueList[] = $this->language->translateOption($v, $attribute, $entity->getEntityType());
            }

            return implode(', ', $valueList);
        }

        if ($attributeType === 'date') {
            if (!$value) {
                return '';
            }

            return $this->dateTime->convertSystemDate($value);
        }

        if ($attributeType === 'datetime') {
            if (!$value) {
                return '';
            }

            return $this->dateTime->convertSystemDateTime($value);
        }

        if ($attributeType === 'text') {
            if (!is_string($value)) {
                return '';
            }

            return nl2br($value);
        }

        if ($attributeType === 'float') {
            if (!is_float($value)) {
                return '';
            }

            $decimalPlaces = 2;

            if ($fieldType === 'currency') {
                $decimalPlaces = $this->config->get('currencyDecimalPlaces');
            }

            return $this->number->format($value, $decimalPlaces);
        }

        if ($attributeType === 'int') {
            if (!is_int($value)) {
                return '';
            }

            if (
                $fieldType === 'autoincrement' ||
                $fieldType === 'int' &&
                $this->metadata
                    ->get(['entityDefs', $entity->getEntityType(), 'fields', $attribute, 'disableFormatting'])
            ) {
                return (string) $value;
            }

            return $this->number->format($value);
        }

        if (!is_string($value) && is_scalar($value) || is_callable([$value, '__toString'])) {
            return strval($value);
        }

        if ($value === null) {
            return '';
        }

        if (!is_string($value)) {
            return null;
        }

        return $value;
    }
}
