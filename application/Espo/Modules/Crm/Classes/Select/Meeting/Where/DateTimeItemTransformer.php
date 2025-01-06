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

namespace Espo\Modules\Crm\Classes\Select\Meeting\Where;

use Espo\Core\Select\Where\DateTimeItemTransformer as DateTimeItemTransformerInterface;
use Espo\Core\Select\Where\DefaultDateTimeItemTransformer;
use Espo\Core\Select\Where\Item;

/**
 * Extends to take into account DateStartDate and DateEndDate fields.
 *
 * @noinspection PhpUnused
 */
class DateTimeItemTransformer implements DateTimeItemTransformerInterface
{
    public function __construct(
        private DefaultDateTimeItemTransformer $defaultDateTimeItemTransformer
    ) {}

    public function transform(Item $item): Item
    {
        $type = $item->getType();
        $value = $item->getValue();
        $attribute = $item->getAttribute();

        $transformedItem = $this->defaultDateTimeItemTransformer->transform($item);

        if (
            !in_array($attribute, ['dateStart', 'dateEnd']) ||
            in_array($type, [
                Item\Type::IS_NULL,
                Item\Type::EVER,
                Item\Type::IS_NOT_NULL,
            ])
        ) {
            return $transformedItem;
        }

        $attributeDate = $attribute . 'Date';

        if (is_string($value)) {
            if (strlen($value) > 11) {
                return $transformedItem;
            }
        } else if (is_array($value)) {
            foreach ($value as $valueItem) {
                if (is_string($valueItem) && strlen($valueItem) > 11) {
                    return $transformedItem;
                }
            }
        }

        $datePartRaw = [
            'attribute' => $attributeDate,
            'type' => $type,
            'value' => $value,
        ];

        $data = $item->getData();

        if ($data instanceof Item\Data\DateTime) {
            $datePartRaw['timeZone'] = $data->getTimeZone();
        }

        $raw = [
            'type' => Item::TYPE_OR,
            'value' => [
                $datePartRaw,
                [
                    'type' => Item::TYPE_AND,
                    'value' => [
                        $transformedItem->getRaw(),
                        [
                            'type' => Item\Type::IS_NULL,
                            'attribute' => $attributeDate,
                        ]
                    ]

                ]
            ]
        ];

        return Item::fromRaw($raw);
    }
}
