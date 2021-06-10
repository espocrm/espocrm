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

namespace Espo\Core\Utils\Database\Orm\Fields;

class PersonName extends Base
{
    protected function load($fieldName, $entityType)
    {
        $format = $this->config->get('personNameFormat');

        switch ($format) {
            case 'lastFirst':
                $subList = ['last' . ucfirst($fieldName), ' ', 'first' . ucfirst($fieldName)];
                break;

            case 'lastFirstMiddle':
                $subList = [
                    'last' . ucfirst($fieldName), ' ', 'first' . ucfirst($fieldName), ' ', 'middle' . ucfirst($fieldName)
                ];
                break;

            case 'firstMiddleLast':
                $subList = [
                    'first' . ucfirst($fieldName), ' ', 'middle' . ucfirst($fieldName), ' ', 'last' . ucfirst($fieldName)
                ];
                break;

            default:
                $subList = ['first' . ucfirst($fieldName), ' ', 'last' . ucfirst($fieldName)];
        }

        if ($format === 'lastFirstMiddle' || $format === 'lastFirst') {
            $orderBy1Field = 'last' . ucfirst($fieldName);
            $orderBy2Field = 'first' . ucfirst($fieldName);
        } else {
            $orderBy1Field = 'first' . ucfirst($fieldName);
            $orderBy2Field = 'last' . ucfirst($fieldName);
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

        $uname = ucfirst($fieldName);

        $firstName = 'first' . $uname;
        $middleName = 'middle' . $uname;
        $lastName = 'last' . $uname;

        $whereItems[] = "CONCAT:({$firstName}, ' ', {$lastName})";
        $whereItems[] = "CONCAT:({$lastName}, ' ', {$firstName})";

        if ($format === 'firstMiddleLast') {
            $whereItems[] = "CONCAT:({$firstName}, ' ', {$middleName}, ' ', {$lastName})";
        } else
        if ($format === 'lastFirstMiddle') {
            $whereItems[] = "CONCAT:({$lastName}, ' ', {$firstName}, ' ', {$middleName})";
        }

        $selectExpression = $this->getSelect($fullList);

        $selectForeignExpression = $this->getSelect($fullList, '{alias}');

        if ($format === 'firstMiddleLast' || $format === 'lastFirstMiddle') {
            $selectExpression = "REPLACE:({$selectExpression}, '  ', ' ')";
            $selectForeignExpression = "REPLACE:({$selectForeignExpression}, '  ', ' ')";
        }

        $fieldDefs = [
            'type' => 'varchar',
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
                            array_map(
                                function ($item) {
                                    return $item . '*';
                                },
                                $whereItems
                            ),
                            '{value}'
                        ),
                    ],
                ],
                'NOT LIKE' => [
                    'whereClause' => [
                        'AND' => array_fill_keys(
                            array_map(
                                function ($item) {
                                    return $item . '!*';
                                },
                                $whereItems
                            ),
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
        ];

        $dependeeAttributeList = $this->getMetadata()->get(
            ['entityDefs', $entityType, 'fields', $fieldName, 'dependeeAttributeList']
        );

        if ($dependeeAttributeList) {
            $fieldDefs['dependeeAttributeList'] = $dependeeAttributeList;
        }

        return [
            $entityType => [
                'fields' => [
                    $fieldName => $fieldDefs,
                ],
            ],
        ];
    }

    protected function getSelect(array $fullList, ?string $alias = null): string
    {
        foreach ($fullList as &$item) {

            $rowItem = trim($item, " '");

            if (empty($rowItem)) {
                continue;
            }

            if ($alias) {
                $item = $alias . '.' . $item;
            }

            $item = "IFNULL:({$item}, '')";
        }

        $select = "NULLIF:(TRIM:(CONCAT:(" . implode(", ", $fullList) . ")), '')";

        return $select;
    }
}
