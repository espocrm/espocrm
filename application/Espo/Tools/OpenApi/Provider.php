<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Tools\OpenApi;

use Espo\Core\Acl\GlobalRestriction;
use Espo\Core\Acl\Table;
use Espo\Core\AclManager;
use Espo\Core\Select\Where\Item\Type as WhereItemType;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Type\RelationType;
use ReflectionClass;
use stdClass;

/**
 * @todo Cache.
 */
class Provider
{
    public function __construct(
        private Metadata $metadata,
        private Defs $defs,
        private FieldSchemaBuilderFactory $fieldSchemaBuilderFactory,
        private AclManager $aclManager,
        private FieldUtil $fieldUtil,
    ) {}

    public function get(): string
    {
        $spec = (object) [
            'openapi' => '3.1.1',
            'info' => [
                'title' => 'EspoCRM API',
                'version' => '1.0.0',
            ],
            'paths' => [],
            'components' => [
                'schemas' => $this->buildSchemas(),
                'securitySchemes' => [
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-Api-Key',
                    ],
                ]
            ],
            'security' => [
                ['ApiKeyAuth' => []],
            ],
            'servers' => [
                [
                    'url' => '{siteUrl}/api/v1',
                    'variables' => [
                        'siteUrl' => [
                            'default' => 'http://localhost',
                            'description' => 'An URL of you Espo instance.'
                        ]
                    ]
                ]
            ]
        ];

        $this->buildCrud($spec);

        return Json::encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildSchemas(): array
    {
        $output = [];

        foreach ($this->getEntityTypeList() as $entityType) {
            $entitySchemaName = $this->composeEntityTypeObjectSchemaName($entityType);

            $schema = $this->buildEntityTypeObjectSchema($entityType);

            $required = $schema['required'];
            unset($schema['required']);

            $output[$entitySchemaName] = $schema;

            $createSchema = [
                '$ref' => "#/components/schemas/$entitySchemaName",
                'type' => Type::OBJECT,
            ];

            $createSchema['required'] = $required;

            if (!$createSchema['required']) {
                unset($createSchema['required']);
            }

            $output[$entitySchemaName . '_create'] = $createSchema;
        }

        $output['whereItem'] = $this->buildWhereItemSchema();

        return $output;
    }

    /**
     * @return string[]
     */
    private function getEntityTypeList(): array
    {
        /** @var array<string, array<string, mixed>> $defs */
        $defs = $this->metadata->get('scopes') ?? [];

        $output = [];

        foreach ($defs as $scope => $it) {
            $object = $it['object'] ?? false;
            $entity = $it['entity'] ?? false;
            $disabled = $it['disabled'] ?? false;
            $module = $it['module'] ?? false;

            if ($module === 'Custom') {
                continue;
            }

            if (!$object || !$entity) {
                continue;
            }

            if ($disabled) {
                continue;
            }

            $output[] = $scope;
        }

        usort($output, function ($a, $b) {
            return strcmp($a, $b);
        });

        return $output;
    }

    private function composeEntityTypeObjectSchemaName(string $entityType): string
    {
        return $entityType;
    }

    /**
     * @return array{'type': string, 'required': string[], 'properties': array<string, mixed>}
     */
    private function buildEntityTypeObjectSchema(string $entityType): array
    {
        $fieldDefsList = $this->defs->getEntity($entityType)->getFieldList();

        $forbiddenList = $this->aclManager->getScopeRestrictedFieldList($entityType, GlobalRestriction::TYPE_FORBIDDEN);
        $internalList = $this->aclManager->getScopeRestrictedFieldList($entityType, GlobalRestriction::TYPE_INTERNAL);
        $readOnlyList = $this->aclManager->getScopeRestrictedFieldList($entityType, [
            GlobalRestriction::TYPE_READ_ONLY,
            GlobalRestriction::TYPE_NON_ADMIN_READ_ONLY,
            GlobalRestriction::TYPE_ONLY_ADMIN,
        ]);

        $properties = [];

        $id = $this->buildEntityTypeObjectFieldSchema($entityType, Attribute::ID);

        $properties = array_merge($properties, $id->properties);

        $required = [];

        foreach ($fieldDefsList as $fieldDefs) {
            $field = $fieldDefs->getName();

            if (in_array($field, $forbiddenList)) {
                continue;
            }

            if ($fieldDefs->getParam('isCustom')) {
                continue;
            }

            if (
                $fieldDefs->getParam(FieldParam::DISABLED) ||
                $fieldDefs->getParam(FieldParam::UTILITY) ||
                $fieldDefs->getParam('apiSpecDisabled')
            ) {
                continue;
            }

            $isReadOnly = in_array($field, $readOnlyList);
            $isInternal = in_array($field, $internalList);

            $itResult = $this->buildEntityTypeObjectFieldSchema($entityType, $field);

            foreach ($itResult->properties as $attribute => $attributeSchema) {
                if ($isReadOnly) {
                    $attributeSchema['readOnly'] = true;
                }

                if ($isInternal) {
                    $attributeSchema['writeOnly'] = true;
                }

                $properties[$attribute] = $attributeSchema;
            }

            foreach ($itResult->required as $attribute) {
                $required[] = $attribute;
            }

            $required = array_unique($required);
            $required = array_values($required);
        }

        return [
            'type' => Type::OBJECT,
            'properties' => $properties,
            'required' => $required,
        ];
    }

    private function buildEntityTypeObjectFieldSchema(string $entityType, string $field): FieldSchemaResult
    {
        $builder = $this->fieldSchemaBuilderFactory->create($entityType, $field);

        return $builder->build($entityType, $field);
    }

    private function buildCrud(stdClass $spec): void
    {
        foreach ($this->getEntityTypeList() as $entityType) {
            $this->buildCrudForEntityType($spec, $entityType);
        }
    }

    private function buildCrudForEntityType(stdClass $spec, string $entityType): void
    {
        $pathItemRoot = (object) [];
        $pathItemRoot->post = $this->prepareOperationCreate($entityType);
        $pathItemRoot->get = $this->prepareOperationList($entityType);

        $pathItemRecord = (object) [
            'parameters' => [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'schema' => [
                        'type' => Type::STRING,
                    ],
                    'description' => 'A record ID.',
                ]
            ]
        ];

        $pathItemRecord->get = $this->prepareOperationGet($entityType);
        $pathItemRecord->patch = $this->prepareOperationPatch($entityType);
        $pathItemRecord->delete = $this->prepareOperationDelete($entityType);

        $aclActionList = $this->metadata->get("scopes.$entityType.aclActionList");
        $noRead = false;

        if (is_array($aclActionList)) {
            if (!in_array(Table::ACTION_CREATE, $aclActionList)) {
                unset($pathItemRoot->post);
            }

            if (!in_array(Table::ACTION_READ, $aclActionList)) {
                unset($pathItemRoot->get);
                unset($pathItemRecord->get);

                $noRead = true;
            }

            if (!in_array(Table::ACTION_EDIT, $aclActionList)) {
                unset($pathItemRecord->patch);
            }

            if (!in_array(Table::ACTION_DELETE, $aclActionList)) {
                unset($pathItemRecord->delete);
            }
        }

        $spec->paths["/$entityType"] = get_object_vars($pathItemRoot);
        $spec->paths["/$entityType/{id}"] = get_object_vars($pathItemRecord);

        if (!$noRead) {
            $this->addLinks($entityType, $spec);
        }
    }

    private function getEntityTypeRef(string $entityType): string
    {
        return "#/components/schemas/$entityType";
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareOperationCreate(string $entityType): array
    {
        $ref = $this->getEntityTypeRef($entityType);

        $createOperation = [
            'tags' => [$entityType],
            'requestBody' => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => $ref . '_create',
                        ],
                    ]
                ]
            ],
            'parameters' => [
                [
                    'in' => 'header',
                    'name' => 'X-Skip-Duplicate-Check',
                    'schema' => [
                        'type' => Type::STRING,
                        'enum' => [
                            'true',
                            'false',
                        ],
                    ],
                    'description' => 'Skip duplicate check.'
                ],
                [
                    'in' => 'header',
                    'name' => 'X-Duplicate-Source-Id',
                    'schema' => [
                        'type' => Type::STRING,
                    ],
                    'description' => 'A record ID of the entity that is being duplicated.',
                ]
            ],
            'description' => "Create a new '$entityType' record.",
        ];

        $responses = [];

        $responses['200'] = [
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => $ref,
                    ],
                ],
            ],
            'description' => 'Success.',
        ];

        $responses['400'] = [
            'description' => 'Bad request. Might be a validation error. Check logs for details.',
        ];

        $responses['403'] = [
            'description' => 'Forbidden. Might be an access control error. Check logs for details.',
        ];

        $responses['409'] = [
            'description' => 'Conflict. May be a possible duplicate. Use X-Skip-Duplicate-Check to skip check.',
        ];

        $createOperation['responses'] = $responses;

        return $createOperation;
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareOperationPatch(string $entityType): array
    {
        $ref = $this->getEntityTypeRef($entityType);

        $parameters = [];

        if ($this->metadata->get(['recordDefs', $entityType, 'updateDuplicateCheck']) ) {
            $parameters[] =
                [
                    'in' => 'header',
                    'name' => 'X-Skip-Duplicate-Check',
                    'schema' => [
                        'type' => Type::STRING,
                        'enum' => [
                            'true',
                            'false',
                        ],
                    ],
                    'description' => 'Skip duplicate check.'
                ];
        }

        if ($this->metadata->get(['entityDefs', $entityType, 'optimisticConcurrencyControl'])) {
            $parameters[] =
                [
                    'in' => 'header',
                    'name' => 'X-Version-Number',
                    'schema' => [
                        'type' => Type::STRING,
                    ],
                    'description' => "A version number for optimistic concurrency control. " .
                        "Obtained from the 'versionNumber' field."
                ];
        }
        $operation = [
            'tags' => [$entityType],
            'requestBody' => [
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => $ref],
                    ]
                ]
            ],
            'parameters' => $parameters,
            'description' => "Update an existing '$entityType' record.",
        ];

        $responses = [];

        $responses['200'] = [
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => $ref,
                    ],
                ],
            ],
            'description' => 'Success.',
        ];

        $responses['400'] = [
            'description' => 'Bad request. Might be a validation error. Check logs for details.',
        ];

        $responses['403'] = [
            'description' => 'Forbidden. Might be an access control error. Check logs for details.',
        ];

        $responses['409'] = [
            'description' => 'Conflict.',
        ];

        $operation['responses'] = $responses;

        return $operation;
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareOperationDelete(string $entityType): array
    {
        $operation = [
            'tags' => [$entityType],
            'description' => "Remove an existing '$entityType' record.",
        ];

        $responses = [];

        $responses['200'] = [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => Type::BOOLEAN,
                        'description' => 'Always true. Do not check the value.',
                    ],
                ],
            ],
            'description' => 'Success.',
        ];

        $responses['400'] = [
            'description' => 'Bad request. Check logs for details.',
        ];

        $responses['403'] = [
            'description' => 'Forbidden. Might be an access control error. Check logs for details.',
        ];

        $operation['responses'] = $responses;

        return $operation;
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareOperationGet(string $entityType): array
    {
        $ref = $this->getEntityTypeRef($entityType);

        $operation = [
            'tags' => [$entityType],
            'description' => "Read an existing '$entityType' record.",
        ];

        $responses = [];

        $responses['200'] = [
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => $ref,
                    ],
                ],
            ],
            'description' => 'Success.',
        ];

        $responses['403'] = [
            'description' => 'Forbidden. Might be an access control error. Check logs for details.',
        ];

        $operation['responses'] = $responses;

        return $operation;
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareOperationList(string $entityType): array
    {
        $parameters = [];

        $parameters[] =
            [
                'in' => 'header',
                'name' => 'X-No-Total',
                'schema' => [
                    'type' => Type::STRING,
                    'enum' => [
                        'true',
                        'false',
                    ],
                ],
                'description' => 'Disable calculation of the total number of records.',
            ];

        $parameters[] = $this->prepareSearchParam($entityType);

        return [
            'tags' => [$entityType],
            'parameters' => $parameters,
            'description' => "List $entityType records.",
            'responses' => $this->prepareListResponses($entityType),
        ];
    }

    /**
     * @return string[]
     */
    private function getSelectAttributeList(string $entityType): array
    {
        $fieldDefsList = $this->defs->getEntity($entityType)->getFieldList();

        $ignoreList = $this->aclManager->getScopeRestrictedFieldList($entityType, [
            GlobalRestriction::TYPE_FORBIDDEN,
            GlobalRestriction::TYPE_INTERNAL,
        ]);

        $output = [];

        foreach ($fieldDefsList as $fieldDefs) {
            if (in_array($fieldDefs->getName(), $ignoreList)) {
                continue;
            }

            $output = array_merge(
                $output,
                $this->fieldUtil->getAttributeList($entityType, $fieldDefs->getName())
            );
        }

        $output = array_unique($output);

        return array_values($output);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWhereItemSchema(): array
    {
        $whereItemTypes = array_values((new ReflectionClass(WhereItemType::class))->getConstants());

        return [
            'type' => Type::OBJECT,
            'properties' => [
                'type' => [
                    'type' => Type::STRING,
                    'enum' => $whereItemTypes,
                    'description' => 'An operator.',
                ],
                'attribute' => [
                    'type' => Type::STRING,
                    'description' => 'An attribute or field.',
                ],
                'value' => [
                    'oneOf' => [
                        [
                            'type' => [
                                Type::STRING,
                                Type::INTEGER,
                                Type::NUMBER,
                                Type::BOOLEAN,
                                Type::NULL,
                            ]
                        ],
                        [
                            'type' => Type::ARRAY,
                            'items' => [
                                '$ref' => '#/components/schemas/whereItem',
                            ]
                        ],
                        [
                            'type' => Type::ARRAY,
                            'items' => [
                                'type' => Type::STRING,
                            ],
                        ],
                    ],
                    'description' => 'A value. A scalar, or an array of strings.',
                ],
                'dateTime' => [
                    'type' => Type::BOOLEAN,
                    'description' => "Set true for date-time fields.",
                ],
                "timeZone" => [
                    'type' => Type::STRING,
                    'description' => "A time zone. For date-time fields.",
                ],
            ],
            'required' => ['type'],
            'description' => 'A where item.',
        ];

    }

    /**
     * @return array<string, mixed>
     */
    private function prepareSearchParam(string $entityType): array
    {
        $searchParamsProperties = [
            'offset' => [
                'type' => Type::INTEGER,
                'minimum' => 0,
                'description' => 'A pagination offset.',
            ],
            'maxSize' => [
                'type' => Type::INTEGER,
                'minimum' => 0,
                'maximum' => 200,
                'description' => 'The maximum number of records to return.',
            ],
            'orderBy' => [
                'type' => Type::STRING,
                'description' => 'An attribute (field) to order by.',
            ],
            'order' => [
                'type' => Type::STRING,
                'enum' => [
                    'asc',
                    'desc',
                ],
                'description' => 'An order direction.',
            ],
            'textFilter' => [
                'type' => Type::STRING,
                'description' => 'A text filter query. Wildcard (*) is supported.'
            ],
        ];

        $primaryFilterList = array_keys($this->metadata->get("selectDefs.$entityType.primaryFilterClassNameMap") ?? []);

        $boolFilterList = array_map(
            function ($it) {
                if (is_array($it)) {
                    return $it['name'] ?? null;
                }

                return $it;
            },
            $this->metadata->get("clientDefs.$entityType.boolFilterList") ?? []
        );

        if ($primaryFilterList) {
            $searchParamsProperties['primaryFilter'] = [
                'type' => Type::STRING,
                'enum' => $primaryFilterList,
                'description' => 'A primary filter.',
            ];
        }

        if ($boolFilterList) {
            $searchParamsProperties['boolFilterList'] = [
                'type' => Type::ARRAY,
                'items' => [
                    'type' => Type::STRING,
                    'enum' => $boolFilterList,
                ],
                'description' => 'Bool filters.',
            ];
        }

        $selectAttributeList = $this->getSelectAttributeList($entityType);

        if ($selectAttributeList) {
            $searchParamsProperties['select'] = [
                'type' => Type::ARRAY,
                'items' => [
                    'type' => Type::STRING,
                    'enum' => $selectAttributeList,
                ],
                'description' => 'Attributes to return. Select only the necessary ones to improve performance.',
            ];
        }

        $searchParamsProperties['where'] = [
            'type' => Type::ARRAY,
            'items' => [
                '$ref' => '#/components/schemas/whereItem',
            ],
            'description' => 'Where items.',
        ];

        return [
            'in' => 'query',
            'name' => 'searchParams',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => Type::OBJECT,
                        'properties' => $searchParamsProperties,
                    ],
                ],
            ],
            'description' => 'Disable calculation of the total number of records.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareList200Response(string $entityType): array
    {
        $ref = $this->getEntityTypeRef($entityType);

        return [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => Type::OBJECT,
                        'properties' => [
                            'list' => [
                                'type' => Type::ARRAY,
                                'items' => [
                                    '$ref' => $ref,
                                ],
                                'description' => 'Records.'
                            ],
                            'total' => [
                                'type' => Type::NUMBER,
                                'description' => "The total number of records. " .
                                    "If the total number is disabled, special values are returned: " .
                                    "-1 – there are more records to paginate, -2 – there are no more records."
                            ],
                        ],
                    ],
                ],
            ],
            'description' => 'Success.',
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function prepareListResponses(string $entityType): array
    {
        $responses = [];

        $responses['200'] = $this->prepareList200Response($entityType);

        $responses['400'] = [
            'description' => 'Bad request. Check logs for details.',
        ];

        $responses['403'] = [
            'description' => 'Forbidden. Might be an access control error. Check logs for details.',
        ];

        return $responses;
    }

    private function addLinks(string $entityType, stdClass $spec): void
    {
        $relationList = $this->defs->getEntity($entityType)->getRelationList();

        usort($relationList, function (Defs\RelationDefs $a, Defs\RelationDefs $b) {
            return strcmp($a->getName(), $b->getName());
        });

        $restrictedList = $this->aclManager->getScopeRestrictedLinkList($entityType, [
            GlobalRestriction::TYPE_FORBIDDEN,
        ]);

        foreach ($relationList as $defs) {
            $link = $defs->getName();

            if (
                in_array($link, $restrictedList) ||
                !in_array($defs->getType(), [
                    RelationType::HAS_MANY,
                    RelationType::MANY_MANY,
                    RelationType::HAS_CHILDREN,
                ]) ||
                (
                    $this->metadata->get("entityDefs.$entityType.links.$link.readOnly") ||
                    $this->metadata->get("entityDefs.$entityType.links.$link.disabled")
                ) ||
                $defs->getParam('isCustom')
            ) {
                continue;
            }

            $foreignEntityType = $this->defs
                ->getEntity($entityType)
                ->tryGetRelation($link)
                ?->tryGetForeignEntityType();

            if (!$foreignEntityType) {
                continue;
            }

            $this->addLink($entityType, $link, $spec);
        }
    }

    private function addLink(string $entityType, string $link, stdClass $spec): void
    {
        $foreignEntityType = $this->defs
            ->getEntity($entityType)
            ->getRelation($link)
            ->getForeignEntityType();

        if (!in_array($foreignEntityType, $this->getEntityTypeList())) {
            return;
        }

        $pathItem = [
            'parameters' => [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'schema' => [
                        'type' => Type::STRING,
                    ],
                    'description' => 'A record ID.',
                ]
            ]
        ];

        $pathItem['get'] = $this->prepareOperationListLink($entityType, $link, $foreignEntityType);
        $pathItem['post'] = $this->prepareOperationPostLink($entityType, $link, $foreignEntityType);
        $pathItem['delete'] = $this->prepareOperationDeleteLink($entityType, $link, $foreignEntityType);

        $spec->paths["/$entityType/{id}/$link"] = $pathItem;
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareOperationListLink(string $entityType, string $link, string $foreignEntityType): array
    {
        $parameters = [];

        $searchParam = $this->prepareSearchParam($foreignEntityType);

        $parameters[] = $searchParam;

        $operation = [
            'tags' => [$entityType],
            'parameters' => $parameters,
            'description' => "List '$foreignEntityType' records related through the '$link' link.",
        ];

        $responses = $this->prepareListResponses($foreignEntityType);

        $operation['responses'] = $responses;

        return $operation;
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareOperationPostLink(string $entityType, string $link, string $foreignEntityType): array
    {
        $createOperation = [
            'tags' => [$entityType],
            'requestBody' => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => Type::OBJECT,
                            'properties' => [
                                'id' => [
                                    'type' => Type::STRING,
                                    'description' => "An ID of $foreignEntityType record.",
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            'description' => "Relate '$foreignEntityType' record though the '$link' link.",
        ];

        $responses = [];

        $responses['200'] = [
            'description' => 'Success.',
        ];

        $responses['400'] = [
            'description' => 'Bad request. Might be a validation error. Check logs for details.',
        ];

        $responses['403'] = [
            'description' => 'Forbidden. Might be an access control error. Check logs for details.',
        ];

        $createOperation['responses'] = $responses;

        return $createOperation;
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareOperationDeleteLink(string $entityType, string $link, string $foreignEntityType): array
    {
        $createOperation = [
            'tags' => [$entityType],
            'requestBody' => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => Type::OBJECT,
                            'properties' => [
                                'id' => [
                                    'type' => Type::STRING,
                                    'description' => "An ID of $foreignEntityType record.",
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            'description' => "Unrelate '$foreignEntityType' record related though the '$link' link.",
        ];

        $responses = [];

        $responses['200'] = [
            'description' => 'Success.',
        ];

        $responses['400'] = [
            'description' => 'Bad request. Might be a validation error. Check logs for details.',
        ];

        $responses['403'] = [
            'description' => 'Forbidden. Might be an access control error. Check logs for details.',
        ];

        $createOperation['responses'] = $responses;

        return $createOperation;
    }
}
