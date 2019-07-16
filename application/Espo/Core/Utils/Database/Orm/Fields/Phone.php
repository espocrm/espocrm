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

class Phone extends Base
{
    protected function load($fieldName, $entityType)
    {
        $foreignJoinAlias = "{$fieldName}{$entityType}Foreign";
        $foreignJoinMiddleAlias = "{$fieldName}{$entityType}ForeignMiddle";

        return [
            $entityType => [
                'fields' => [
                    $fieldName => array(
                        'select' => [
                            'sql' => 'phoneNumbers.name',
                            'leftJoins' => [['phoneNumbers', 'phoneNumbers', ['primary' => 1]]],
                        ],
                        'selectForeign' => [
                            'sql' => "{$foreignJoinAlias}.name",
                            'leftJoins' => [
                                [
                                    'EntityPhoneNumber',
                                    $foreignJoinMiddleAlias,
                                    [
                                        "{$foreignJoinMiddleAlias}.entityId:" => "{alias}.id",
                                        "{$foreignJoinMiddleAlias}.primary" => 1,
                                    ]
                                ],
                                [
                                    'PhoneNumber',
                                    $foreignJoinAlias,
                                    [
                                        "{$foreignJoinAlias}.id:" => "{$foreignJoinMiddleAlias}.phoneNumberId",
                                    ]
                                ]
                            ],
                        ],
                        'fieldType' => 'phone',
                        'where' =>
                        array (
                            'LIKE' => \Espo\Core\Utils\Util::toUnderScore($entityType) . ".id IN (
                                SELECT entity_id
                                FROM entity_phone_number
                                JOIN phone_number ON phone_number.id = entity_phone_number.phone_number_id
                                WHERE
                                    entity_phone_number.deleted = 0 AND entity_phone_number.entity_type = '{$entityType}' AND
                                    phone_number.deleted = 0 AND phone_number.name LIKE {value}
                            )",
                            '=' => array(
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                                'sql' => 'phoneNumbersMultiple.name = {value}',
                                'distinct' => true
                            ),
                            '<>' => array(
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                                'sql' => 'phoneNumbersMultiple.name <> {value}',
                                'distinct' => true
                            ),
                            'IN' => array(
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                                'sql' => 'phoneNumbersMultiple.name IN {value}',
                                'distinct' => true
                            ),
                            'NOT IN' => array(
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                                'sql' => 'phoneNumbersMultiple.name NOT IN {value}',
                                'distinct' => true
                            ),
                            'IS NULL' => array(
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                                'sql' => 'phoneNumbersMultiple.name IS NULL',
                                'distinct' => true
                            ),
                            'IS NOT NULL' => array(
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                                'sql' => 'phoneNumbersMultiple.name IS NOT NULL',
                                'distinct' => true
                            )
                        ),
                        'orderBy' => [
                            'sql' => 'phoneNumbers.name {direction}',
                            'leftJoins' => [['phoneNumbers', 'phoneNumbers', ['primary' => 1]]],
                        ],
                    ),
                    $fieldName .'Data' => array(
                        'type' => 'text',
                        'notStorable' => true,
                        'notExportable' => true,
                    ),
                    $fieldName .'IsOptedOut' => [
                        'type' => 'bool',
                        'notStorable' => true,
                        'select' => 'phoneNumbers.opt_out',
                        'where' => [
                            '= TRUE' => [
                                'sql' => 'phoneNumbers.opt_out = true AND phoneNumbers.opt_out IS NOT NULL',
                                'leftJoins' => [['phoneNumbers', 'phoneNumbers', ['primary' => 1]]],
                            ],
                            '= FALSE' => [
                                'sql' => 'phoneNumbers.opt_out = false OR phoneNumbers.opt_out IS NULL',
                                'leftJoins' => [['phoneNumbers', 'phoneNumbers', ['primary' => 1]]],
                            ]
                        ],
                        'orderBy' => 'phoneNumbers.opt_out {direction}'
                    ],
                    $fieldName . 'Numeric' => [
                        'type' => 'varchar',
                        'notStorable' => true,
                        'notExportable' => true,
                        'where' => [
                            'LIKE' => \Espo\Core\Utils\Util::toUnderScore($entityType) . ".id IN (
                                SELECT entity_id
                                FROM entity_phone_number
                                JOIN phone_number ON phone_number.id = entity_phone_number.phone_number_id
                                WHERE
                                    entity_phone_number.deleted = 0 AND entity_phone_number.entity_type = '{$entityType}' AND
                                    phone_number.deleted = 0 AND phone_number.numeric LIKE {value}
                            )",
                            '=' => [
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersNumericMultiple']],
                                'sql' => 'phoneNumbersNumericMultiple.numeric = {value}',
                                'distinct' => true
                            ],
                            '<>' => [
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersNumericMultiple']],
                                'sql' => 'phoneNumbersNumericMultiple.numeric <> {value}',
                                'distinct' => true
                            ],
                            'IN' => [
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersNumericMultiple']],
                                'sql' => 'phoneNumbersNumericMultiple.numeric IN {value}',
                                'distinct' => true
                            ],
                            'NOT IN' => [
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersNumericMultiple']],
                                'sql' => 'phoneNumbersNumericMultiple.numeric NOT IN {value}',
                                'distinct' => true
                            ],
                            'IS NULL' => [
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersNumericMultiple']],
                                'sql' => 'phoneNumbersNumericMultiple.numeric IS NULL',
                                'distinct' => true
                            ],
                            'IS NOT NULL' => [
                                'leftJoins' => [['phoneNumbers', 'phoneNumbersNumericMultiple']],
                                'sql' => 'phoneNumbersNumericMultiple.numeric IS NOT NULL',
                                'distinct' => true
                            ]
                        ],
                    ]
                ],
                'relations' => [
                    'phoneNumbers' => [
                        'type' => 'manyMany',
                        'entity' => 'PhoneNumber',
                        'relationName' => 'entityPhoneNumber',
                        'midKeys' => [
                            'entityId',
                            'phoneNumberId'
                        ],
                        'conditions' => [
                            'entityType' => $entityType
                        ],
                        'additionalColumns' => [
                            'entityType' => [
                                'type' => 'varchar',
                                'len' => 100
                            ],
                            'primary' => [
                                'type' => 'bool',
                                'default' => false
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
