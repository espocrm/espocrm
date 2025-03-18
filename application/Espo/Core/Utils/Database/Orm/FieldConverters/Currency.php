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

namespace Espo\Core\Utils\Database\Orm\FieldConverters;

use Doctrine\DBAL\Types\Types;
use Espo\Core\Currency\ConfigDataProvider;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Database\Orm\Defs\AttributeDefs;
use Espo\Core\Utils\Database\Orm\Defs\EntityDefs;
use Espo\Core\Utils\Database\Orm\FieldConverter;
use Espo\ORM\Defs\FieldDefs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Type\AttributeType;

class Currency implements FieldConverter
{
    private const DEFAULT_PRECISION = 13;
    private const DEFAULT_SCALE = 4;

    public function __construct(
        private Config $config,
        private ConfigDataProvider $configDataProvider
    ) {}

    public function convert(FieldDefs $fieldDefs, string $entityType): EntityDefs
    {
        $name = $fieldDefs->getName();

        $amountDefs = AttributeDefs::create($name)
            ->withType(AttributeType::FLOAT)
            ->withParamsMerged([
                'attributeRole' => 'value',
                'fieldType' => FieldType::CURRENCY,
            ]);

        $currencyDefs = AttributeDefs::create($name . 'Currency')
            ->withType(AttributeType::VARCHAR)
            ->withParamsMerged([
                'attributeRole' => 'currency',
                'fieldType' => FieldType::CURRENCY,
            ]);

        $convertedDefs = null;

        if ($fieldDefs->getParam(FieldParam::DECIMAL)) {
            $dbType = $fieldDefs->getParam(FieldParam::DB_TYPE) ?? Types::DECIMAL;
            $precision = $fieldDefs->getParam(FieldParam::PRECISION) ?? self::DEFAULT_PRECISION;
            $scale = $fieldDefs->getParam(FieldParam::SCALE) ?? self::DEFAULT_SCALE;

            $amountDefs = $amountDefs
                ->withType(AttributeType::VARCHAR)
                ->withDbType($dbType)
                ->withParam(AttributeParam::PRECISION, $precision)
                ->withParam(AttributeParam::SCALE, $scale);
        }

        if ($fieldDefs->isNotStorable()) {
            $amountDefs = $amountDefs->withNotStorable();
            $currencyDefs = $currencyDefs->withNotStorable();
        }

        if (!$fieldDefs->isNotStorable()) {
            [$amountDefs, $convertedDefs] = $this->config->get('currencyNoJoinMode') ?
                $this->applyNoJoinMode($fieldDefs, $amountDefs) :
                $this->applyJoinMode($fieldDefs, $amountDefs, $entityType);
        }

        $entityDefs = EntityDefs::create()
            ->withAttribute($amountDefs)
            ->withAttribute($currencyDefs);

        if ($convertedDefs) {
            $entityDefs = $entityDefs->withAttribute($convertedDefs);
        }

        return $entityDefs;
    }

    /**
     * @return array{AttributeDefs, AttributeDefs}
     */
    private function applyNoJoinMode(FieldDefs $fieldDefs, AttributeDefs $amountDefs): array
    {
        $name = $fieldDefs->getName();

        $currencyAttribute = $name . 'Currency';

        $defaultCurrency = $this->configDataProvider->getDefaultCurrency();
        $baseCurrency = $this->configDataProvider->getBaseCurrency();
        $rates = $this->configDataProvider->getCurrencyRates()->toAssoc();

        if ($defaultCurrency !== $baseCurrency) {
            $rates = $this->exchangeRates($baseCurrency, $defaultCurrency, $rates);
        }

        $expr = Expr::multiply(
            Expr::column($name),
            Expr::if(
                Expr::equal(Expr::column($currencyAttribute), $defaultCurrency),
                1.0,
                $this->buildExpression($currencyAttribute, $rates)
            )
        )->getValue();

        $exprForeign = Expr::multiply(
            Expr::column("ALIAS.{$name}"),
            Expr::if(
                Expr::equal(Expr::column("ALIAS.{$name}Currency"), $defaultCurrency),
                1.0,
                $this->buildExpression("ALIAS.{$name}Currency", $rates)
            )
        )->getValue();

        $exprForeign = str_replace('ALIAS', '{alias}', $exprForeign);

        $convertedDefs = AttributeDefs::create($name . 'Converted')
            ->withType(AttributeType::FLOAT)
            ->withParamsMerged([
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
                AttributeParam::NOT_STORABLE => true,
                'order' => [
                    'order' => [
                        [$expr, '{direction}'],
                    ],
                ],
                'attributeRole' => 'valueConverted',
                'fieldType' => FieldType::CURRENCY,
            ]);

        return [$amountDefs, $convertedDefs];
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

    /**
     * @return array{AttributeDefs, AttributeDefs}
     */
    private function applyJoinMode(FieldDefs $fieldDefs, AttributeDefs $amountDefs, string $entityType): array
    {
        $name = $fieldDefs->getName();

        $alias = $name . 'CurrencyRate';
        $leftJoins = [
            [
                'Currency',
                $alias,
                [$alias . '.id:' => $name . 'Currency'],
            ]
        ];
        $foreignCurrencyAlias = "{$alias}{$entityType}{alias}Foreign";
        $mulExpression = "MUL:({$name}, {$alias}.rate)";

        $amountDefs = $amountDefs->withParamsMerged([
            'order' => [
                'order' => [
                    [$mulExpression, '{direction}'],
                ],
                'leftJoins' => $leftJoins,
                'additionalSelect' => ["{$alias}.rate"],
            ]
        ]);

        $convertedDefs = AttributeDefs::create($name . 'Converted')
            ->withType(AttributeType::FLOAT)
            ->withParamsMerged([
                'select' => [
                    'select' => $mulExpression,
                    'leftJoins' => $leftJoins,
                ],
                'selectForeign' => [
                    'select' => "MUL:({alias}.{$name}, {$foreignCurrencyAlias}.rate)",
                    'leftJoins' => [
                        [
                            'Currency',
                            $foreignCurrencyAlias,
                            [$foreignCurrencyAlias . '.id:' => "{alias}.{$name}Currency"]
                        ]
                    ],
                ],
                'where' => [
                    "=" => [
                        'whereClause' => [$mulExpression . '=' => '{value}'],
                        'leftJoins' => $leftJoins,
                    ],
                    ">" => [
                        'whereClause' => [$mulExpression . '>' => '{value}'],
                        'leftJoins' => $leftJoins,
                    ],
                    "<" => [
                        'whereClause' => [$mulExpression . '<' => '{value}'],
                        'leftJoins' => $leftJoins,
                    ],
                    ">=" => [
                        'whereClause' => [$mulExpression . '>=' => '{value}'],
                        'leftJoins' => $leftJoins,
                    ],
                    "<=" => [
                        'whereClause' => [$mulExpression . '<=' => '{value}'],
                        'leftJoins' => $leftJoins,
                    ],
                    "<>" => [
                        'whereClause' => [$mulExpression . '!=' => '{value}'],
                        'leftJoins' => $leftJoins,
                    ],
                    "IS NULL" => [
                        'whereClause' => [$name . '=' => null],
                    ],
                    "IS NOT NULL" => [
                        'whereClause' => [$name . '!=' => null],
                    ],
                ],
                AttributeParam::NOT_STORABLE => true,
                'order' => [
                    'order' => [
                        [$mulExpression, '{direction}'],
                    ],
                    'leftJoins' => $leftJoins,
                    'additionalSelect' => ["{$alias}.rate"],
                ],
                'attributeRole' => 'valueConverted',
                'fieldType' => FieldType::CURRENCY,
            ]);

        return [$amountDefs, $convertedDefs];
    }
}
