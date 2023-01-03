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

namespace Espo\Core\Utils\Database\Orm\Fields;

use Espo\ORM\Entity;
use Espo\ORM\Query\Part\Expression as Expr;

class Currency extends Base
{
    private const DEFAULT_PRECISION = 13;
    private const DEFAULT_SCALE = 4;

    /**
     * @param string $fieldName
     * @param string $entityType
     * @return array<string, mixed>
     */
    protected function load($fieldName, $entityType)
    {
        $defs = [
            $entityType => [
                'fields' => [
                    $fieldName => [
                        'type' => 'float',
                    ]
                ]
            ],
        ];

        $params = $this->getFieldParams($fieldName);

        if (!empty($params['decimal'])) {
            if (empty($params['dbType'])) {
                $defs[$entityType]['fields'][$fieldName]['dbType'] = 'decimal';
            }

            if (!isset($params['precision'])) {
                $defs[$entityType]['fields'][$fieldName]['precision'] = self::DEFAULT_PRECISION;
            }

            if (!isset($params['scale'])) {
                $defs[$entityType]['fields'][$fieldName]['scale'] = self::DEFAULT_SCALE;
            }

            $defs[$entityType]['fields'][$fieldName]['type'] = Entity::VARCHAR;
        }

        if (!empty($params['notStorable'])) {
            $defs[$entityType]['fields'][$fieldName]['notStorable'] = true;
        }
        else {
            if ($this->config->get('currencyNoJoinMode')) {
                $this->applyNoJoinMode($fieldName, $entityType, $defs);

            }
            else {
                $this->applyJoinMode($fieldName, $entityType, $defs);
            }
        }

        $defs[$entityType]['fields'][$fieldName]['attributeRole'] = 'value';
        $defs[$entityType]['fields'][$fieldName]['fieldType'] = 'currency';

        $defs[$entityType]['fields'][$fieldName . 'Currency']['attributeRole'] = 'currency';
        $defs[$entityType]['fields'][$fieldName . 'Currency']['fieldType'] = 'currency';

        return $defs;
    }

    /**
     * @param string $fieldName
     * @param string $entityType
     * @param array<string, mixed> $defs
     */
    private function applyJoinMode(
        string $fieldName,
        string $entityType,
        array &$defs
    ): void {

        $alias = $fieldName . 'CurrencyRate';
        $leftJoins = [
            [
                'Currency',
                $alias,
                [$alias . '.id:' => $fieldName . 'Currency'],
            ]
        ];
        $foreignCurrencyAlias = "{$alias}{$entityType}{alias}Foreign";
        $mulExpression = "MUL:({$fieldName}, {$alias}.rate)";

        $defs[$entityType]['fields'][$fieldName . 'Converted'] = [
            'type' => 'float',
            'select' => [
                'select' => $mulExpression,
                'leftJoins' => $leftJoins,
            ],
            'selectForeign' => [
                'select' => "MUL:({alias}.{$fieldName}, {$foreignCurrencyAlias}.rate)",
                'leftJoins' => [
                    [
                        'Currency',
                        $foreignCurrencyAlias,
                        [
                            $foreignCurrencyAlias . '.id:' => "{alias}.{$fieldName}Currency",
                        ]
                    ]
                ],
            ],
            'where' => [
                "=" => [
                    'whereClause' => [
                        $mulExpression . '=' => '{value}',
                    ],
                    'leftJoins' => $leftJoins,
                ],
                ">" => [
                    'whereClause' => [
                        $mulExpression . '>' => '{value}',
                    ],
                    'leftJoins' => $leftJoins,
                ],
                "<" => [
                    'whereClause' => [
                        $mulExpression . '<' => '{value}',
                    ],
                    'leftJoins' => $leftJoins,
                ],
                ">=" => [
                    'whereClause' => [
                        $mulExpression . '>=' => '{value}',
                    ],
                    'leftJoins' => $leftJoins,
                ],
                "<=" => [
                    'whereClause' => [
                        $mulExpression . '<=' => '{value}',
                    ],
                    'leftJoins' => $leftJoins,
                ],
                "<>" => [
                    'whereClause' => [
                        $mulExpression . '!=' => '{value}',
                    ],
                    'leftJoins' => $leftJoins,
                ],
                "IS NULL" => [
                    'whereClause' => [
                        $fieldName . '=' => null,
                    ],
                ],
                "IS NOT NULL" => [
                    'whereClause' => [
                        $fieldName . '!=' => null,
                    ],
                ],
            ],
            'notStorable' => true,
            'order' => [
                'order' => [
                    [$mulExpression, '{direction}'],
                ],
                'leftJoins' => $leftJoins,
                'additionalSelect' => ["{$alias}.rate"],
            ],
            'attributeRole' => 'valueConverted',
            'fieldType' => 'currency',
        ];

        $defs[$entityType]['fields'][$fieldName]['order'] = [
            "order" => [
                [$mulExpression, '{direction}'],
            ],
            'leftJoins' => $leftJoins,
            'additionalSelect' => ["{$alias}.rate"],
        ];
    }

    /**
     * @param string $fieldName
     * @param string $entityType
     * @param array<string, mixed> $defs
     */
    private function applyNoJoinMode(
        string $fieldName,
        string $entityType,
        array &$defs
    ): void {

        $currencyAttribute = $fieldName . 'Currency';

        $defaultCurrency = $this->config->get('defaultCurrency');
        $baseCurrency = $this->config->get('baseCurrency');
        $rates = $this->config->get('currencyRates');

        if ($defaultCurrency !== $baseCurrency) {
            $rates = $this->exchangeRates($baseCurrency, $defaultCurrency, $rates);
        }

        $expr = Expr::multiply(
            Expr::column($fieldName),
            Expr::if(
                Expr::equal(Expr::column($currencyAttribute), $defaultCurrency),
                1.0,
                $this->buildExpression($currencyAttribute, $rates)
            )
        )->getValue();

        $exprForeign = Expr::multiply(
            Expr::column("ALIAS.{$fieldName}"),
            Expr::if(
                Expr::equal(Expr::column("ALIAS.{$fieldName}Currency"), $defaultCurrency),
                1.0,
                $this->buildExpression("ALIAS.{$fieldName}Currency", $rates)
            )
        )->getValue();

        $exprForeign = str_replace('ALIAS', '{alias}', $exprForeign);

        $defs[$entityType]['fields'][$fieldName . 'Converted'] = [
            'type' => 'float',
            'select' => [
                'select' => $expr,
            ],
            'selectForeign' => [
                'select' => $exprForeign,
            ],
            'where' => [
                "=" => [
                    'whereClause' => [
                        $expr . '=' => '{value}',
                    ],
                ],
                ">" => [
                    'whereClause' => [
                        $expr . '>' => '{value}',
                    ],
                ],
                "<" => [
                    'whereClause' => [
                        $expr . '<' => '{value}',
                    ],
                ],
                ">=" => [
                    'whereClause' => [
                        $expr . '>=' => '{value}',
                    ],
                ],
                "<=" => [
                    'whereClause' => [
                        $expr . '<=' => '{value}',
                    ],
                ],
                "<>" => [
                    'whereClause' => [
                        $expr . '!=' => '{value}',
                    ],
                ],
                "IS NULL" => [
                    'whereClause' => [
                        $expr . '=' => null,
                    ],
                ],
                "IS NOT NULL" => [
                    'whereClause' => [
                        $expr . '!=' => null,
                    ],
                ],
            ],
            'notStorable' => true,
            'order' => [
                'order' => [
                    [$expr, '{direction}'],
                ],
            ],
            'attributeRole' => 'valueConverted',
            'fieldType' => 'currency',
        ];
    }

    /**
     * @param array<string, float> $currencyRates
     * @return array<string, float>
     */
    private function exchangeRates(string $baseCurrency, string $defaultCurrency, array $currencyRates): array
    {
        $precision = 5;
        $defaultCurrencyRate = round(1 / $currencyRates[$defaultCurrency], $precision);

        $exchangedRates = [];
        $exchangedRates[$baseCurrency] = $defaultCurrencyRate;

        unset($currencyRates[$baseCurrency], $currencyRates[$defaultCurrency]);

        foreach ($currencyRates as $currencyName => $rate) {
            $exchangedRates[$currencyName] = round($rate * $defaultCurrencyRate, $precision);
        }

        return $exchangedRates;
    }

    /**
     * @param array<string, float> $rates
     */
    private function buildExpression(string $currencyAttribute, array $rates): Expr|float
    {
        if ($rates === []) {
            return 0.0;
        }

        $currency = array_key_first($rates);
        $value = $rates[$currency];
        unset($rates[$currency]);

        return Expr::if(
            Expr::equal(Expr::column($currencyAttribute), $currency),
            $value,
            $this->buildExpression($currencyAttribute, $rates)
        );
    }
}
