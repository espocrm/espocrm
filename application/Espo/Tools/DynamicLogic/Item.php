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

namespace Espo\Tools\DynamicLogic;

use Espo\Tools\DynamicLogic\Exceptions\BadCondition;
use stdClass;

readonly class Item
{
    public function __construct(
        public Type $type,
        public mixed $value,
        public ?string $attribute = null,
    ) {}

    /**
     * @param stdClass[] $rawItems
     * @throws BadCondition
     */
    public static function fromGroupDefinition(array $rawItems): Item
    {
        return new Item(
            type: Type::And,
            value: array_map(fn ($it) => self::fromItemDefinition($it), $rawItems),
        );
    }

    /**
     * @throws BadCondition
     */
    public static function fromItemDefinition(stdClass $rawItem): Item
    {
        $type = $rawItem->type ?? null;
        $attribute = $rawItem->attribute ?? null;
        $value = $rawItem->value ?? null;

        if (!$type || !is_string($type)) {
            throw new BadCondition("No type.");
        }

        if ($type === 'has') {
            $type = 'contains';
        }

        if ($type === Type::And->value || $type === Type::Or->value) {
            if (!is_array($value)) {
                throw new BadCondition("Non-array value.");
            }

            foreach ($value as $it) {
                if (!$it instanceof stdClass) {
                    throw new BadCondition("Bad group item value.");
                }
            }

            return new Item(
                type: Type::from($type),
                value: array_map(fn ($it) => self::fromItemDefinition($it), $value),
            );
        }

        if ($type === Type::Not->value) {
            if (!$value instanceof stdClass) {
                throw new BadCondition("Bad not item value.");
            }

            return new Item(
                type: Type::from($type),
                value: self::fromItemDefinition($value),
            );
        }

        if ($attribute !== null && !is_string($attribute)) {
            throw new BadCondition("No attribute.");
        }

        return new Item(
            type: Type::from($type),
            value: $value,
            attribute: $attribute,
        );
    }
}
