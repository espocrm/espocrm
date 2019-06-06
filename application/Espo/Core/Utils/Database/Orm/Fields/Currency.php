<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils\Database\Orm\Fields;

use Espo\Core\Utils\Util;

class Currency extends Base
{
    protected function load($fieldName, $entityType)
    {
        $converedFieldName = $fieldName . 'Converted';

        $currencyColumnName = Util::toUnderScore($fieldName);

        $alias = $fieldName . 'CurrencyRate';

        $defs = [
            $entityType => [
                'fields' => [
                    $fieldName => [
                        'type' => 'float',
                    ]
                ]
            ]
        ];

        $part = Util::toUnderScore($entityType) . "." . $currencyColumnName;
        $leftJoins = [
            [
                'Currency',
                $alias,
                [$alias . '.id:' => $fieldName . 'Currency']
            ]
        ];

        $foreignAlias = "{$alias}{$entityType}Foreign";

        $params = $this->getFieldParams($fieldName);
        if (!empty($params['notStorable'])) {
            $defs[$entityType]['fields'][$fieldName]['notStorable'] = true;
        } else {
            $defs[$entityType]['fields'][$fieldName . 'Converted'] = [
                'type' => 'float',
                'select' => [
                    'sql' => $part . " * {$alias}.rate",
                    'leftJoins' => $leftJoins,
                ],
                'selectForeign' => [
                    'sql' => "{alias}.{$currencyColumnName} * {$foreignAlias}.rate",
                    'leftJoins' => [
                        [
                            'Currency',
                            $foreignAlias,
                            [
                                $foreignAlias . '.id:' => "{alias}.{$fieldName}Currency"
                            ]
                        ]
                    ],
                ],
                'where' =>
                [
                        "=" => ['sql' => $part . " * {$alias}.rate = {value}", 'leftJoins' => $leftJoins],
                        ">" => ['sql' => $part . " * {$alias}.rate > {value}", 'leftJoins' => $leftJoins],
                        "<" => ['sql' => $part . " * {$alias}.rate < {value}", 'leftJoins' => $leftJoins],
                        ">=" => ['sql' => $part . " * {$alias}.rate >= {value}", 'leftJoins' => $leftJoins],
                        "<=" => ['sql' => $part . " * {$alias}.rate <= {value}", 'leftJoins' => $leftJoins],
                        "<>" => ['sql' => $part . " * {$alias}.rate <> {value}", 'leftJoins' => $leftJoins],
                        "IS NULL" => ['sql' => $part . ' IS NULL'],
                        "IS NOT NULL" => ['sql' => $part . ' IS NOT NULL'],
                ],
                'notStorable' => true,
                'orderBy' => [
                    'sql' => $converedFieldName . " {direction}",
                    'leftJoins' => $leftJoins,
                ],
                'attributeRole' => 'valueConverted',
                'fieldType' => 'currency',
            ];

            $defs[$entityType]['fields'][$fieldName]['orderBy'] = [
                'sql' => $part . " * {$alias}.rate {direction}",
                'leftJoins' => $leftJoins,
            ];
        }

        $defs[$entityType]['fields'][$fieldName]['attributeRole'] = 'value';
        $defs[$entityType]['fields'][$fieldName]['fieldType'] = 'currency';

        $defs[$entityType]['fields'][$fieldName . 'Currency']['attributeRole'] = 'currency';
        $defs[$entityType]['fields'][$fieldName . 'Currency']['fieldType'] = 'currency';

        return $defs;
    }
}
