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

class Email extends Base
{
    /**
     * @param string $fieldName
     * @param string $entityType
     * @return array<string,mixed>
     */
    protected function load($fieldName, $entityType)
    {
        $foreignJoinAlias = "{$fieldName}{$entityType}{alias}Foreign";
        $foreignJoinMiddleAlias = "{$fieldName}{$entityType}{alias}ForeignMiddle";

        $mainFieldDefs = [
            'type' => 'varchar',
            'select' => [
                "select" => "emailAddresses.name",
                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => true]]],
            ],
            'selectForeign' => [
                "select" => "{$foreignJoinAlias}.name",
                'leftJoins' => [
                    [
                        'EntityEmailAddress',
                        $foreignJoinMiddleAlias,
                        [
                            "{$foreignJoinMiddleAlias}.entityId:" => "{alias}.id",
                            "{$foreignJoinMiddleAlias}.primary" => true,
                            "{$foreignJoinMiddleAlias}.deleted" => false,
                        ]
                    ],
                    [
                        'EmailAddress',
                        $foreignJoinAlias,
                        [
                            "{$foreignJoinAlias}.id:" => "{$foreignJoinMiddleAlias}.emailAddressId",
                            "{$foreignJoinAlias}.deleted" => false,
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
                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => true]]],
                'additionalSelect' => ['emailAddresses.lower'],
            ],
        ];

        return [
            $entityType => [
                'fields' => [
                    $fieldName => $mainFieldDefs,
                    $fieldName . 'Data' => [
                        'type' => Entity::JSON_ARRAY,
                        'notStorable' => true,
                        'notExportable' => true,
                        'isEmailAddressData' => true,
                        'field' => $fieldName,
                    ],
                    $fieldName .'IsOptedOut' => [
                        'type' => 'bool',
                        'notStorable' => true,
                        'select' => [
                            'select' => "emailAddresses.optOut",
                            'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => true]]],
                        ],
                        'selectForeign' => [
                            'select' => "{$foreignJoinAlias}.optOut",
                            'leftJoins' => [
                                [
                                    'EntityEmailAddress',
                                    $foreignJoinMiddleAlias,
                                    [
                                        "{$foreignJoinMiddleAlias}.entityId:" => "{alias}.id",
                                        "{$foreignJoinMiddleAlias}.primary" => true,
                                        "{$foreignJoinMiddleAlias}.deleted" => false,
                                    ]
                                ],
                                [
                                    'EmailAddress',
                                    $foreignJoinAlias,
                                    [
                                        "{$foreignJoinAlias}.id:" => "{$foreignJoinMiddleAlias}.emailAddressId",
                                        "{$foreignJoinAlias}.deleted" => false,
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
                                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => true]]],
                            ],
                            '= FALSE' => [
                                'whereClause' => [
                                    'OR' => [
                                        ['emailAddresses.optOut=' => false],
                                        ['emailAddresses.optOut=' => null],
                                    ]
                                ],
                                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => true]]],
                            ]
                        ],
                        'order' => [
                            'order' => [
                                ['emailAddresses.optOut', '{direction}'],
                            ],
                            'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => true]]],
                            'additionalSelect' => ['emailAddresses.optOut'],
                        ],
                    ],
                    $fieldName .'IsInvalid' => [
                        'type' => 'bool',
                        'notStorable' => true,
                        'select' => [
                            'select' => "emailAddresses.invalid",
                            'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => true]]],
                        ],
                        'selectForeign' => [
                            'select' => "{$foreignJoinAlias}.invalid",
                            'leftJoins' => [
                                [
                                    'EntityEmailAddress',
                                    $foreignJoinMiddleAlias,
                                    [
                                        "{$foreignJoinMiddleAlias}.entityId:" => "{alias}.id",
                                        "{$foreignJoinMiddleAlias}.primary" => true,
                                        "{$foreignJoinMiddleAlias}.deleted" => false,
                                    ]
                                ],
                                [
                                    'EmailAddress',
                                    $foreignJoinAlias,
                                    [
                                        "{$foreignJoinAlias}.id:" => "{$foreignJoinMiddleAlias}.emailAddressId",
                                        "{$foreignJoinAlias}.deleted" => false,
                                    ]
                                ]
                            ],
                        ],
                        'where' => [
                            '= TRUE' => [
                                'whereClause' => [
                                    ['emailAddresses.invalid=' => true],
                                    ['emailAddresses.invalid!=' => null],
                                ],
                                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => true]]],
                            ],
                            '= FALSE' => [
                                'whereClause' => [
                                    'OR' => [
                                        ['emailAddresses.invalid=' => false],
                                        ['emailAddresses.invalid=' => null],
                                    ]
                                ],
                                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => true]]],
                            ]
                        ],
                        'order' => [
                            'order' => [
                                ['emailAddresses.invalid', '{direction}'],
                            ],
                            'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => true]]],
                            'additionalSelect' => ['emailAddresses.invalid'],
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
