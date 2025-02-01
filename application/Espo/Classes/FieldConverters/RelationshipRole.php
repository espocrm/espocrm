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

namespace Espo\Classes\FieldConverters;

use Espo\Core\Utils\Database\Orm\Defs\AttributeDefs;
use Espo\Core\Utils\Database\Orm\Defs\EntityDefs;
use Espo\Core\Utils\Database\Orm\FieldConverter;
use Espo\ORM\Defs\FieldDefs;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Type\AttributeType;
use RuntimeException;

class RelationshipRole implements FieldConverter
{
    public function convert(FieldDefs $fieldDefs, string $entityType): EntityDefs
    {
        $name = $fieldDefs->getName();

        $attributeDefs = AttributeDefs::create($name)
            ->withType(AttributeType::VARCHAR)
            ->withNotStorable();

        $attributeDefs = $this->addWhere($attributeDefs, $fieldDefs, $entityType);

        return EntityDefs::create()
            ->withAttribute($attributeDefs);
    }

    private function addWhere(AttributeDefs $attributeDefs, FieldDefs $fieldDefs, string $entityType): AttributeDefs
    {
        $data = $fieldDefs->getParam('converterData');

        if (!is_array($data)) {
            throw new RuntimeException("No `converterData` in field defs.");
        }

        /** @var ?string $column */
        $column = $data['column'] ?? null;
        /** @var ?string $link */
        $link = $data['link'] ?? null;
        /** @var ?string $relationName */
        $relationName = $data[RelationParam::RELATION_NAME] ?? null;
        /** @var ?string $nearKey */
        $nearKey = $data['nearKey'] ?? null;

        if (!$column || !$link || !$relationName || !$nearKey) {
            throw new RuntimeException("Bad `converterData`.");
        }

        $midTable = ucfirst($relationName);

        return $attributeDefs->withParamsMerged([
            'where' => [
                '=' => [
                    'whereClause' => [
                        'id=s' => [
                            'from' => $midTable,
                            'select' => [$nearKey],
                            'whereClause' => [
                                Attribute::DELETED => false,
                                $column => '{value}',
                            ],
                        ],
                    ],
                ],
                '<>' => [
                    'whereClause' => [
                        'id!=s' => [
                            'from' => $midTable,
                            'select' => [$nearKey],
                            'whereClause' => [
                                Attribute::DELETED => false,
                                $column => '{value}',
                            ],
                        ],
                    ],
                ],
                'IN' => [
                    'whereClause' => [
                        'id=s' => [
                            'from' => $midTable,
                            'select' => [$nearKey],
                            'whereClause' => [
                                Attribute::DELETED => false,
                                $column => '{value}',
                            ],
                        ],
                    ],
                ],
                'NOT IN' => [
                    'whereClause' => [
                        'id!=s' => [
                            'from' => $midTable,
                            'select' => [$nearKey],
                            'whereClause' => [
                                Attribute::DELETED => false,
                                $column => '{value}',
                            ],
                        ],
                    ],
                ],
                'LIKE' => [
                    'whereClause' => [
                        'id=s' => [
                            'from' => $midTable,
                            'select' => [$nearKey],
                            'whereClause' => [
                                Attribute::DELETED => false,
                                "$column*" => '{value}',
                            ],
                        ],
                    ],
                ],
                'NOT LIKE' => [
                    'whereClause' => [
                        'id!=s' => [
                            'from' => $midTable,
                            'select' => [$nearKey],
                            'whereClause' => [
                                Attribute::DELETED => false,
                                "$column*" => '{value}',
                            ],
                        ],
                    ],
                ],
                'IS NULL' => [
                    'whereClause' => [
                        'NOT' => [
                            'EXISTS' => [
                                'from' => $entityType,
                                'fromAlias' => 'sq',
                                'select' => [Attribute::ID],
                                'leftJoins' => [
                                    [
                                        $link,
                                        'm',
                                        null,
                                        ['onlyMiddle' => true]
                                    ]
                                ],
                                'whereClause' => [
                                    "m.$column!=" => null,
                                    'sq.id:' => lcfirst($entityType) . '.id',
                                ],
                            ],
                        ],
                    ],
                ],
                'IS NOT NULL' => [
                    'whereClause' => [
                        'EXISTS' => [
                            'from' => $entityType,
                            'fromAlias' => 'sq',
                            'select' => [Attribute::ID],
                            'leftJoins' => [
                                [
                                    $link,
                                    'm',
                                    null,
                                    ['onlyMiddle' => true]
                                ]
                            ],
                            'whereClause' => [
                                "m.$column!=" => null,
                                'sq.id:' => lcfirst($entityType) . '.id',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
