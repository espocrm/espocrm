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

class Email extends Base
{
    protected function load($fieldName, $entityType)
    {
        $foreignJoinAlias = "{$fieldName}{$entityType}Foreign";
        $foreignJoinMiddleAlias = "{$fieldName}{$entityType}ForeignMiddle";

        return [
            $entityType => [
                'fields' => [
                    $fieldName => [
                        'select' => [
                            'sql' => 'emailAddresses.name',
                            'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => 1]]],
                        ],
                        'selectForeign' => [
                            'sql' => "{$foreignJoinAlias}.name",
                            'leftJoins' => [
                                [
                                    'EntityEmailAddress',
                                    $foreignJoinMiddleAlias,
                                    [
                                        "{$foreignJoinMiddleAlias}.entityId:" => "{alias}.id",
                                        "{$foreignJoinMiddleAlias}.primary" => 1,
                                    ]
                                ],
                                [
                                    'EmailAddress',
                                    $foreignJoinAlias,
                                    [
                                        "{$foreignJoinAlias}.id:" => "{$foreignJoinMiddleAlias}.emailAddressId",
                                    ]
                                ]
                            ],
                        ],
                        'fieldType' => 'email',
                        'where' => [
                            'LIKE' => \Espo\Core\Utils\Util::toUnderScore($entityType) . ".id IN (
                                SELECT entity_id
                                FROM entity_email_address
                                JOIN email_address ON email_address.id = entity_email_address.email_address_id
                                WHERE
                                    entity_email_address.deleted = 0 AND entity_email_address.entity_type = '{$entityType}' AND
                                    email_address.deleted = 0 AND email_address.lower LIKE {value}
                            )",
                            '=' => array(
                                'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                                'sql' => 'emailAddressesMultiple.lower = {value}',
                                'distinct' => true
                            ),
                            '<>' => array(
                                'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                                'sql' => 'emailAddressesMultiple.lower <> {value}',
                                'distinct' => true
                            ),
                            'IN' => array(
                                'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                                'sql' => 'emailAddressesMultiple.lower IN {value}',
                                'distinct' => true
                            ),
                            'NOT IN' => array(
                                'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                                'sql' => 'emailAddressesMultiple.lower NOT IN {value}',
                                'distinct' => true
                            ),
                            'IS NULL' => array(
                                'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                                'sql' => 'emailAddressesMultiple.lower IS NULL',
                                'distinct' => true
                            ),
                            'IS NOT NULL' => array(
                                'leftJoins' => [['emailAddresses', 'emailAddressesMultiple']],
                                'sql' => 'emailAddressesMultiple.lower IS NOT NULL',
                                'distinct' => true
                            )
                        ],
                        'orderBy' => [
                            'sql' => 'emailAddresses.lower {direction}',
                            'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => 1]]],
                        ],
                    ],
                    $fieldName .'Data' => [
                        'type' => 'text',
                        'notStorable' => true,
                        'notExportable' => true,
                    ],
                    $fieldName .'IsOptedOut' => [
                        'type' => 'bool',
                        'notStorable' => true,
                        'select' => 'emailAddresses.opt_out',
                        'where' => [
                            '= TRUE' => [
                                'sql' => 'emailAddresses.opt_out = true AND emailAddresses.opt_out IS NOT NULL',
                                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => 1]]],
                            ],
                            '= FALSE' => [
                                'sql' => 'emailAddresses.opt_out = false OR emailAddresses.opt_out IS NULL',
                                'leftJoins' => [['emailAddresses', 'emailAddresses', ['primary' => 1]]],
                            ]
                        ],
                        'orderBy' => 'emailAddresses.opt_out {direction}'
                    ]
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
