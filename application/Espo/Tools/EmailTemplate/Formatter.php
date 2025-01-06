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

namespace Espo\Tools\EmailTemplate;

use Espo\Core\ORM\Type\FieldType;
use Espo\ORM\Entity;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\NumberUtil;
use Espo\Core\Utils\Language;

use Espo\ORM\Type\AttributeType;
use Stringable;

class Formatter
{
    public function __construct(
        private Metadata $metadata,
        private Config $config,
        private DateTimeUtil $dateTime,
        private NumberUtil $number,
        private Language $language
    ) {}

    public function formatAttributeValue(Entity $entity, string $attribute, bool $isPlainText = false): ?string
    {
        $value = $entity->get($attribute);

        $fieldType = $this->metadata
            ->get(['entityDefs', $entity->getEntityType(), 'fields', $attribute, 'type']);

        $attributeType = $entity->getAttributeType($attribute);

        if ($fieldType === FieldType::ENUM) {
            if ($value === null) {
                return '';
            }

            $label = $this->language->translateOption($value, $attribute, $entity->getEntityType());

            $translationPath = $this->metadata->get(
                ['entityDefs', $entity->getEntityType(), 'fields', $attribute, 'translation']
            );

            if ($translationPath) {
                $label = $this->language->get($translationPath . '.' . $value, $label);
            }

            return $label;
        }

        if (
            $fieldType === FieldType::ARRAY ||
            $fieldType === FieldType::MULTI_ENUM ||
            $fieldType === FieldType::CHECKLIST
        ) {
            $valueList = [];

            if (!is_array($value)) {
                return '';
            }

            foreach ($value as $v) {
                $valueList[] = $this->language->translateOption($v, $attribute, $entity->getEntityType());
            }

            return implode(', ', $valueList);
        }

        if ($attributeType === AttributeType::DATE) {
            if (!$value) {
                return '';
            }

            return $this->dateTime->convertSystemDate($value);
        }

        if ($attributeType === AttributeType::DATETIME) {
            if (!$value) {
                return '';
            }

            return $this->dateTime->convertSystemDateTime($value);
        }

        if ($attributeType === AttributeType::TEXT) {
            if (!is_string($value)) {
                return '';
            }

            if ($fieldType === FieldType::WYSIWYG) {
                return $value;
            }

            if ($isPlainText) {
                return $value;
            }

            return nl2br($value);
        }

        if ($attributeType === AttributeType::FLOAT) {
            if (!is_float($value)) {
                return '';
            }

            $decimalPlaces = 2;

            if ($fieldType === FieldType::CURRENCY) {
                $decimalPlaces = $this->config->get('currencyDecimalPlaces');
            }

            return $this->number->format($value, $decimalPlaces);
        }

        if ($attributeType === AttributeType::INT) {
            if (!is_int($value)) {
                return '';
            }

            if (
                $fieldType === FieldType::AUTOINCREMENT ||
                $fieldType === FieldType::INT &&
                $this->metadata
                    ->get(['entityDefs', $entity->getEntityType(), 'fields', $attribute, 'disableFormatting'])
            ) {
                return (string) $value;
            }

            return $this->number->format($value);
        }

        if (
            !is_string($value) && is_scalar($value) ||
            $value instanceof Stringable
        ) {
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
