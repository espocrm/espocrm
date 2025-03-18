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

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Database\Orm\Defs\AttributeDefs;
use Espo\Core\Utils\Database\Orm\Defs\EntityDefs;
use Espo\Core\Utils\Database\Orm\FieldConverter;
use Espo\ORM\Defs\FieldDefs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Type\AttributeType;

/**
 * @noinspection PhpUnused
 */
class PersonName implements FieldConverter
{
    private const FORMAT_LAST_FIRST = 'lastFirst';
    private const FORMAT_LAST_FIRST_MIDDLE = 'lastFirstMiddle';
    private const FORMAT_FIRST_MIDDLE_LAST = 'firstMiddleLast';

    public function __construct(private Config $config) {}

    public function convert(FieldDefs $fieldDefs, string $entityType): EntityDefs
    {
        $format = $this->config->get('personNameFormat');

        $name = $fieldDefs->getName();
        $firstName = 'first' . ucfirst($name);
        $lastName = 'last' . ucfirst($name);
        $middleName = 'middle' . ucfirst($name);

        $subList = match ($format) {
            self::FORMAT_LAST_FIRST => [$lastName, ' ', $firstName],
            self::FORMAT_LAST_FIRST_MIDDLE => [$lastName, ' ', $firstName, ' ', $middleName],
            self::FORMAT_FIRST_MIDDLE_LAST => [$firstName, ' ', $middleName, ' ', $lastName],
            default => [$firstName, ' ', $lastName],
        };

        if (
            $format === self::FORMAT_LAST_FIRST_MIDDLE ||
            $format === self::FORMAT_LAST_FIRST
        ) {
            $orderBy1Field = $lastName;
            $orderBy2Field = $firstName;
        } else {
            $orderBy1Field = $firstName;
            $orderBy2Field = $lastName;
        }

        $fullList = [];
        $whereItems = [];

        foreach ($subList as $subFieldName) {
            $fieldNameTrimmed = trim($subFieldName);

            if (empty($fieldNameTrimmed)) {
                $fullList[] = "'" . $subFieldName . "'";

                continue;
            }

            $fullList[] = $fieldNameTrimmed;
            $whereItems[] = $fieldNameTrimmed;
        }

        $whereItems[] = "CONCAT:($firstName, ' ', $lastName)";
        $whereItems[] = "CONCAT:($lastName, ' ', $firstName)";

        if ($format === self::FORMAT_FIRST_MIDDLE_LAST) {
            $whereItems[] = "CONCAT:($firstName, ' ', $middleName, ' ', $lastName)";
        } else if ($format === self::FORMAT_LAST_FIRST_MIDDLE) {
            $whereItems[] = "CONCAT:($lastName, ' ', $firstName, ' ', $middleName)";
        }

        $selectExpression = $this->getSelect($fullList);
        $selectForeignExpression = $this->getSelect($fullList, '{alias}');

        if (
            $format === self::FORMAT_FIRST_MIDDLE_LAST ||
            $format === self::FORMAT_LAST_FIRST_MIDDLE
        ) {
            $selectExpression = "REPLACE:($selectExpression, '  ', ' ')";
            $selectForeignExpression = "REPLACE:($selectForeignExpression, '  ', ' ')";
        }

        $attributeDefs = AttributeDefs::create($name)
            ->withType(AttributeType::VARCHAR)
            ->withNotStorable()
            ->withParamsMerged([
                'select' => [
                    'select' => $selectExpression,
                ],
                'selectForeign' => [
                    'select' => $selectForeignExpression,
                ],
                'where' => [
                    'LIKE' => [
                        'whereClause' => [
                            'OR' => array_fill_keys(
                                array_map(fn ($item) => $item . '*', $whereItems),
                                '{value}'
                            ),
                        ],
                    ],
                    'NOT LIKE' => [
                        'whereClause' => [
                            'AND' => array_fill_keys(
                                array_map(fn ($item) => $item . '!*', $whereItems),
                                '{value}'
                            ),
                        ],
                    ],
                    '=' => [
                        'whereClause' => [
                            'OR' => array_fill_keys($whereItems, '{value}'),
                        ],
                    ],
                ],
                'order' => [
                    'order' => [
                        [$orderBy1Field, '{direction}'],
                        [$orderBy2Field, '{direction}'],
                    ],
                ],
            ]);

        $dependeeAttributeList = $fieldDefs->getParam(FieldParam::DEPENDEE_ATTRIBUTE_LIST);

        if ($dependeeAttributeList) {
            $attributeDefs = $attributeDefs->withParam(AttributeParam::DEPENDEE_ATTRIBUTE_LIST, $dependeeAttributeList);
        }

        return EntityDefs::create()
            ->withAttribute($attributeDefs);
    }

    /**
     * @param string[] $fullList
     */
    private function getSelect(array $fullList, ?string $alias = null): string
    {
        foreach ($fullList as &$item) {
            $rowItem = trim($item, " '");

            if (empty($rowItem)) {
                continue;
            }

            if ($alias) {
                $item = $alias . '.' . $item;
            }

            $item = "IFNULL:($item, '')";
        }

        return "NULLIF:(TRIM:(CONCAT:(" . implode(", ", $fullList) . ")), '')";
    }
}
