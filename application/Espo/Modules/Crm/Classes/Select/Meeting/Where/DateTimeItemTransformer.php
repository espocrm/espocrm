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

namespace Espo\Modules\Crm\Classes\Select\Meeting\Where;

use Espo\Core\{
    Select\Where\DateTimeItemTransformer as DateTimeItemTransformerOriginal,
    Select\Where\Item,
};

/**
 * Extends to take into account DateStartDate and DateEndDate fields.
 */
class DateTimeItemTransformer extends DateTimeItemTransformerOriginal
{
    public function transform(Item $item): Item
    {
        $type = $item->getType();
        $value = $item->getValue();
        $attribute = $item->getAttribute();

        $tranformedItem = parent::transform($item);

        if (!in_array($attribute, ['dateStart', 'dateEnd'])) {
            return $tranformedItem;
        }

        if (in_array($type, ['isNull', 'ever', 'isNotNull'])) {
            return $tranformedItem;
        }

        $attributeDate = $attribute . 'Date';

        if (is_string($value)) {
            if (strlen($value) > 11) {
                return $tranformedItem;
            }
        }
        else if (is_array($value)) {
            foreach ($value as $valueItem) {
                if (is_string($valueItem) && strlen($valueItem) > 11) {
                    return $tranformedItem;
                }
            }
        }

        $datePartRaw = [
            'attribute' => $attributeDate,
            'type' => $type,
            'value' => $value,
        ];

        $raw = [
            'type' => 'or',
            'value' => [
                $datePartRaw,
                [
                    'type' => 'and',
                    'value' => [
                        $tranformedItem->getRaw(),
                        [
                            'type' => 'isNull',
                            'attribute' => $attributeDate,
                        ]
                    ]

                ]
            ]
        ];

        return Item::fromRaw($raw);
    }
}
