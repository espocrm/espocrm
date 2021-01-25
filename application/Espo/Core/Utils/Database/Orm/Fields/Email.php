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

class Email extends Base
{
    protected function load($fieldName, $entityType)
    {
        $foreignJoinAlias = "{$fieldName}{$entityType}{alias}Foreign";
        $foreignJoinMiddleAlias = "{$fieldName}{$entityType}{alias}ForeignMiddle";

        $mainFieldDefs = [
            'type' => 'varchar',
            'select' => [
                "select" => "emailAddresses.name",
                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => 1]]],
            ],
            'selectForeign' => [
                "select" => "{$foreignJoinAlias}.name",
                'leftJoins' => [
                    [
                        'EntityEmailAddress',
                        $foreignJoinMiddleAlias,
                        [
                            "{$foreignJoinMiddleAlias}.entityId:" => "{alias}.id",
                            "{$foreignJoinMiddleAlias}.primary" => 1,
                            "{$foreignJoinMiddleAlias}.deleted" => 0,
                        ]
                    ],
                    [
                        'EmailAddress',
                        $foreignJoinAlias,
                        [
                            "{$foreignJoinAlias}.id:" => "{$foreignJoinMiddleAlias}.emailAddressId",
                            "{$foreignJoinAlias}.deleted" => 0,
                        ]
                    ]
                ],
            ],
            'fieldType' => 'email',
            'where' => [
                'LIKE' => [
                    'whereClause' => [
                        'id=s' => [
                            'from' => 'EntityEmailAddress',
                            'select' => ['entityId'],
                            'joins' => [
                                [
                                    'emailAddress',
                                    'emailAddress',
                                    [
                                        'emailAddress.id:' => 'emailAddressId',
                                        'emailAddress.deleted' => false,
                                    ],
                                ]
                            ],
                            'whereClause' => [
                                'deleted' => false,
                                'entityType' => $entityType,
                                'emailAddress.lower*' => '{value}',
                            ],
                        ],
                    ],
                ],
                'NOT LIKE' => [
                    'whereClause' => [
                        'id!=s' => [
                            'from' => 'EntityEmailAddress',
                            'select' => ['entityId'],
                            'joins' => [
                                [
                                    'emailAddress',
                                    'emailAddress',
                                    [
                                        'emailAddress.id:' => 'emailAddressId',
                                        'emailAddress.deleted' => false,
                                    ],
                                ]
                            ],
                            'whereClause' => [
                                'deleted' => false,
                                'entityType' => $entityType,
                                'emailAddress.lower*' => '{value}',
                            ],
                        ],
                    ],
                ],
                '=' => [
                    'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                    'whereClause' => [
                        'emailAddressesMultiple.lower=' => '{value}',
                    ],
                    'distinct' => true,
                ],
                '<>' => [
                    'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                    'whereClause' => [
                        'emailAddressesMultiple.lower!=' => '{value}',
                    ],
                    'distinct' => true,
                ],
                'IN' => [
                    'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                    'whereClause' => [
                        'emailAddressesMultiple.lower=' => '{value}',
                    ],
                    'distinct' => true,
                ],
                'NOT IN' => [
                    'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                    'whereClause' => [
                        'emailAddressesMultiple.lower!=' => '{value}',
                    ],
                    'distinct' => true,
                ],
                'IS NULL' => [
                    'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                    'whereClause' => [
                        'emailAddressesMultiple.lower=' => null,
                    ],
                    'distinct' => true,
                ],
                'IS NOT NULL' => [
                    'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                    'whereClause' => [
                        'emailAddressesMultiple.lower!=' => null,
                    ],
                    'distinct' => true,
                ],
            ],
            'order' => [
                'order' => [
                    ['emailAddresses.lower', '{direction}'],
                ],
                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => 1]]],
                'additionalSelect' => ['emailAddresses.lower'],
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
                            'select' => "emailAddresses.optOut",
                            'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => 1]]],
                        ],
                        'selectForeign' => [
                            'select' => "{$foreignJoinAlias}.optOut",
                            'leftJoins' => [
                                [
                                    'EntityEmailAddress',
                                    $foreignJoinMiddleAlias,
                                    [
                                        "{$foreignJoinMiddleAlias}.entityId:" => "{alias}.id",
                                        "{$foreignJoinMiddleAlias}.primary" => 1,
                                        "{$foreignJoinMiddleAlias}.deleted" => 0,
                                    ]
                                ],
                                [
                                    'EmailAddress',
                                    $foreignJoinAlias,
                                    [
                                        "{$foreignJoinAlias}.id:" => "{$foreignJoinMiddleAlias}.emailAddressId",
                                        "{$foreignJoinAlias}.deleted" => 0,
                                    ]
                                ]
                            ],
                        ],
                        'where' => [
                            '= TRUE' => [
                                'whereClause' => [
                                    ['emailAddresses.optOut=' => true],
                                    ['emailAddresses.optOut!=' => null],
                                ],
                                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => 1]]],
                            ],
                            '= FALSE' => [
                                'whereClause' => [
                                    'OR' => [
                                        ['emailAddresses.optOut=' => false],
                                        ['emailAddresses.optOut=' => null],
                                    ]
                                ],
                                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => 1]]],
                            ]
                        ],
                        'order' => [
                            'order' => [
                                ['emailAddresses.optOut', '{direction}'],
                            ],
                            'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => 1]]],
                            'additionalSelect' => ['emailAddresses.optOut'],
                        ],
                    ],
                ],
                'relations' => [
                    'emailAddresses' => [
                        'type' => 'manyMany',
                        'entity' => 'EmailAddress',
                        'relationName' => 'entityEmailAddress',
                        'midKeys' => [
                            'entityId',
                            'emailAddressId'
                        ],
                        'conditions' => [
                            'entityType' => $entityType,
                        ],
                        'additionalColumns' => [
                            'entityType' => [
                                'type' => 'varchar',
                                'len' => 100,
                            ],
                            'primary' => [
                                'type' => 'bool',
                                'default' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
