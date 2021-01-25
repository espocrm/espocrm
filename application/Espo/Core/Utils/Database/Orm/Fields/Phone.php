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

class Phone extends Base
{
    protected function load($fieldName, $entityType)
    {
        $foreignJoinAlias = "{$fieldName}{$entityType}{alias}Foreign";
        $foreignJoinMiddleAlias = "{$fieldName}{$entityType}{alias}ForeignMiddle";

        $mainFieldDefs = [
            'type' => 'varchar',
            'select' => [
                "select" => "phoneNumbers.name",
                'leftJoins' => [['phoneNumbers', 'phoneNumbers', ['primary' => 1]]],
            ],
            'selectForeign' => [
                "select" => "{$foreignJoinAlias}.name",
                'leftJoins' => [
                    [
                        'EntityPhoneNumber',
                        $foreignJoinMiddleAlias,
                        [
                            "{$foreignJoinMiddleAlias}.entityId:" => "{alias}.id",
                            "{$foreignJoinMiddleAlias}.primary" => 1,
                            "{$foreignJoinMiddleAlias}.deleted" => 0,
                        ]
                    ],
                    [
                        'PhoneNumber',
                        $foreignJoinAlias,
                        [
                            "{$foreignJoinAlias}.id:" => "{$foreignJoinMiddleAlias}.phoneNumberId",
                            "{$foreignJoinAlias}.deleted" => 0,
                        ]
                    ]
                ],
            ],
            'fieldType' => 'phone',
            'where' => [
                'LIKE' => [
                    'whereClause' => [
                        'id=s' => [
                            'from' => 'EntityPhoneNumber',
                            'select' => ['entityId'],
                            'joins' => [
                                [
                                    'phoneNumber',
                                    'phoneNumber',
                                    [
                                        'phoneNumber.id:' => 'phoneNumberId',
                                        'phoneNumber.deleted' => false,
                                    ],
                                ],
                            ],
                            'whereClause' => [
                                'deleted' => false,
                                'entityType' => $entityType,
                                'phoneNumber.name*' => '{value}',
                            ],
                        ],
                    ],
                ],
                'NOT LIKE' => [
                    'whereClause' => [
                        'id!=s' => [
                            'from' => 'EntityPhoneNumber',
                            'select' => ['entityId'],
                            'joins' => [
                                [
                                    'phoneNumber',
                                    'phoneNumber',
                                    [
                                        'phoneNumber.id:' => 'phoneNumberId',
                                        'phoneNumber.deleted' => false,
                                    ],
                                ],
                            ],
                            'whereClause' => [
                                'deleted' => false,
                                'entityType' => $entityType,
                                'phoneNumber.name*' => '{value}',
                            ],
                        ],
                    ],
                ],
                '=' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.name=' => '{value}',
                    ],
                    'distinct' => true
                ],
                '<>' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.name!=' => '{value}',
                    ],
                    'distinct' => true
                ],
                'IN' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.name=' => '{value}',
                    ],
                    'distinct' => true
                ],
                'NOT IN' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.name!=' => '{value}',
                    ],
                    'distinct' => true
                ],
                'IS NULL' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.name=' => null,
                    ],
                    'distinct' => true
                ],
                'IS NOT NULL' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.name!=' => null,
                    ],
                    'distinct' => true
                ],
            ],
            'order' => [
                'order' => [
                    ['phoneNumbers.name', '{direction}'],
                ],
                'leftJoins' => [['phoneNumbers', 'phoneNumbers', ['primary' => 1]]],
                'additionalSelect' => ['phoneNumbers.name'],
            ],
        ];

        $numbericFieldDefs = [
            'type' => 'varchar',
            'notStorable' => true,
            'notExportable' => true,
            'where' => [
                'LIKE' => [
                    'whereClause' => [
                        'id=s' => [
                            'from' => 'EntityPhoneNumber',
                            'select' => ['entityId'],
                            'joins' => [
                                [
                                    'phoneNumber',
                                    'phoneNumber',
                                    [
                                        'phoneNumber.id:' => 'phoneNumberId',
                                        'phoneNumber.deleted' => false,
                                    ],
                                ],
                            ],
                            'whereClause' => [
                                'deleted' => false,
                                'entityType' => $entityType,
                                'phoneNumber.numeric*' => '{value}',
                            ],
                        ],
                    ],
                ],
                'NOT LIKE' => [
                    'whereClause' => [
                        'id!=s' => [
                            'from' => 'EntityPhoneNumber',
                            'select' => ['entityId'],
                            'joins' => [
                                [
                                    'phoneNumber',
                                    'phoneNumber',
                                    [
                                        'phoneNumber.id:' => 'phoneNumberId',
                                        'phoneNumber.deleted' => false,
                                    ],
                                ]
                            ],
                            'whereClause' => [
                                'deleted' => false,
                                'entityType' => $entityType,
                                'phoneNumber.numeric*' => '{value}',
                            ],
                        ],
                    ],
                ],
                '=' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.numeric=' => '{value}',
                    ],
                    'distinct' => true
                ],
                '<>' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.numeric!=' => '{value}',
                    ],
                    'distinct' => true
                ],
                'IN' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.numeric=' => '{value}',
                    ],
                    'distinct' => true
                ],
                'NOT IN' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.numeric!=' => '{value}',
                    ],
                    'distinct' => true
                ],
                'IS NULL' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.numeric=' => null,
                    ],
                    'distinct' => true
                ],
                'IS NOT NULL' => [
                    'leftJoins' => [['phoneNumbers', 'phoneNumbersMultiple']],
                    'whereClause' => [
                        'phoneNumbersMultiple.numeric!=' => null,
                    ],
                    'distinct' => true
                ],
            ],
        ];

        return [
            $entityType => [
                'fields' => [
                    $fieldName => $mainFieldDefs,
                    $fieldName . 'Data' => [
                        'type' => 'text',
                        'notStorable' => true,
                        'notExportable' => true,
                    ],
                    $fieldName .'IsOptedOut' => [
                        'type' => 'bool',
                        'notStorable' => true,
                        'select' => [
                            'select' => 'phoneNumbers.optOut',
                            'leftJoins' => [['phoneNumbers', 'phoneNumbers', ['primary' => 1]]],
                        ],
                        'selectForeign' => [
                            'select' => "{$foreignJoinAlias}.optOut",
                            'leftJoins' => [
                                [
                                    'EntityPhoneNumber',
                                    $foreignJoinMiddleAlias,
                                    [
                                        "{$foreignJoinMiddleAlias}.entityId:" => "{alias}.id",
                                        "{$foreignJoinMiddleAlias}.primary" => 1,
                                        "{$foreignJoinMiddleAlias}.deleted" => 0,
                                    ]
                                ],
                                [
                                    'PhoneNumber',
                                    $foreignJoinAlias,
                                    [
                                        "{$foreignJoinAlias}.id:" => "{$foreignJoinMiddleAlias}.phoneNumberId",
                                        "{$foreignJoinAlias}.deleted" => 0,
                                    ]
                                ]
                            ],
                        ],
                        'where' => [
                            '= TRUE' => [
                                'whereClause' => [
                                    ['phoneNumbers.optOut=' => true],
                                    ['phoneNumbers.optOut!=' => null],
                                ],
                                'leftJoins' => [['phoneNumbers', 'phoneNumbers', ['primary' => 1]]],
                            ],
                            '= FALSE' => [
                                'whereClause' => [
                                    'OR' => [
                                        ['phoneNumbers.optOut=' => false],
                                        ['phoneNumbers.optOut=' => null],
                                    ]
                                ],
                                'leftJoins' => [['phoneNumbers', 'phoneNumbers', ['primary' => 1]]],
                            ]
                        ],
                       'order' => [
                            'order' => [
                                ['phoneNumbers.optOut', '{direction}'],
                            ],
                            'leftJoins' => [['phoneNumbers', 'phoneNumbers', ['primary' => 1]]],
                            'additionalSelect' => ['phoneNumbers.optOut'],
                        ],
                    ],
                    $fieldName . 'Numeric' => $numbericFieldDefs,
                ],
                'relations' => [
                    'phoneNumbers' => [
                        'type' => 'manyMany',
                        'entity' => 'PhoneNumber',
                        'relationName' => 'entityPhoneNumber',
                        'midKeys' => ['entityId', 'phoneNumberId'],
                        'conditions' => [
                            'entityType' => $entityType,
                        ],
                        'additionalColumns' => [
                            'entityType' => [
                                'type' => 'varchar',
                                'len' => 100
                            ],
                            'primary' => [
                                'type' => 'bool',
                                'default' => false
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
