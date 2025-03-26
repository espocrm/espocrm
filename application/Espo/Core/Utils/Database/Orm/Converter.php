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

namespace Espo\Core\Utils\Database\Orm;

use Doctrine\DBAL\Types\Types;
use Espo\Core\InjectableFactory;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Defs\AttributeParam as CoreAttributeParam;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Database\ConfigDataProvider;
use Espo\Core\Utils\Database\MetadataProvider;
use Espo\Core\Utils\Util;
use Espo\ORM\Defs\AttributeDefs;
use Espo\ORM\Defs\FieldDefs;
use Espo\ORM\Defs\IndexDefs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\EntityParam;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Defs\Params\IndexParam;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Defs\RelationDefs;
use Espo\ORM\Entity;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Metadata\Helper as MetadataHelper;
use Espo\ORM\Name\Attribute;
use LogicException;

class Converter
{
    /** @var ?array<string, mixed> */
    private ?array $entityDefs = null;

    private string $defaultAttributeType = Entity::VARCHAR;

    private const INDEX_TYPE_UNIQUE = 'unique';
    private const INDEX_TYPE_INDEX = 'index';

    /** @var array<string, int> */
    private array $defaultLengthMap = [
        Entity::VARCHAR => 255,
        Entity::INT => 11,
    ];

    private const FIELD_TYPE_BASE = 'base';

    /**
     * Mapping entityDefs => ORM.
     *
     * @var array<string, string>
     */
    private array $paramMap = [
        FieldParam::TYPE => AttributeParam::TYPE,
        FieldParam::DB_TYPE => AttributeParam::DB_TYPE,
        FieldParam::MAX_LENGTH => AttributeParam::LEN,
        'len' => AttributeParam::LEN, // @todo Revise.
        FieldParam::NOT_NULL => AttributeParam::NOT_NULL,
        'exportDisabled' => CoreAttributeParam::NOT_EXPORTABLE,
        FieldParam::AUTOINCREMENT => AttributeParam::AUTOINCREMENT,
        'entity' => 'entity',
        FieldParam::NOT_STORABLE => AttributeParam::NOT_STORABLE,
        'link' => AttributeParam::RELATION,
        'field' => AttributeParam::FOREIGN,
        'unique' => 'unique',
        'index' => 'index',
        FieldParam::DEFAULT => AttributeParam::DEFAULT,
        'select' => 'select',
        'order' => 'order',
        'where' => 'where',
        'storeArrayValues' => 'storeArrayValues',
        'binary' => 'binary',
        FieldParam::DEPENDEE_ATTRIBUTE_LIST => AttributeParam::DEPENDEE_ATTRIBUTE_LIST,
        FieldParam::PRECISION => AttributeParam::PRECISION,
        FieldParam::SCALE => AttributeParam::SCALE,
    ];

    /** @var array<string, mixed> */
    private array $idParams = [];

    /** @var string[] */
    private array $copyEntityProperties = [EntityParam::INDEXES];

    private IndexHelper $indexHelper;

    public function __construct(
        private Metadata $metadata,
        private RelationConverter $relationConverter,
        private MetadataHelper $metadataHelper,
        private InjectableFactory $injectableFactory,
        ConfigDataProvider $configDataProvider,
        IndexHelperFactory $indexHelperFactory,
        MetadataProvider $metadataProvider
    ) {
        $platform = $configDataProvider->getPlatform();

        $this->indexHelper = $indexHelperFactory->create($platform);

        $this->idParams[AttributeParam::LEN] = $metadataProvider->getIdLength();
        $this->idParams[AttributeParam::DB_TYPE] = $metadataProvider->getIdDbType();
    }

    /**
     * @param bool $reload
     * @return array<string, mixed>
     */
    private function getEntityDefs($reload = false)
    {
        if (empty($this->entityDefs) || $reload) {
            $this->entityDefs = $this->metadata->get('entityDefs');
        }

        return $this->entityDefs;
    }

    /**
     * Covert metadata > entityDefs to ORM metadata.
     *
     * @return array<string, array<string, mixed>>
     */
    public function process(): array
    {
        $entityDefs = $this->getEntityDefs(true);

        $ormMetadata = [];

        foreach ($entityDefs as $entityType => $entityMetadata) {
            if ($entityMetadata['skipRebuild'] ?? false) {
                $ormMetadata[$entityType]['skipRebuild'] = true;
            }

            if ($entityMetadata['modifierClassName'] ?? null) {
                $ormMetadata[$entityType]['modifierClassName'] = $entityMetadata['modifierClassName'];
            }

            /** @var array<string, array<string, mixed>> $ormMetadata */
            $ormMetadata = Util::merge(
                $ormMetadata,
                $this->convertEntity($entityType, $entityMetadata)
            );
        }

        foreach ($ormMetadata as $entityType => $entityOrmMetadata) {
            /** @var array<string, array<string, mixed>> $ormMetadata */
            $ormMetadata = Util::merge(
                $ormMetadata,
                $this->createEntityTypesFromRelations($entityType, $entityOrmMetadata)
            );
        }

        foreach ($entityDefs as $entityMetadata) {
            /** @var array<string, array<string, mixed>> $ormMetadata */
            $ormMetadata = Util::merge(
                $ormMetadata,
                $this->obtainAdditionalTablesOrmMetadata($entityMetadata)
            );
        }

        $ormMetadata = $this->afterFieldsProcess($ormMetadata);

        return $this->afterProcess($ormMetadata);
    }

    private function composeIndexKey(IndexDefs $defs, string $entityType): string
    {
        return $this->indexHelper->composeKey($defs, $entityType);
    }

    /**
     * @param array<string, mixed> $entityMetadata
     * @return array<string, mixed>
     */
    private function convertEntity(string $entityType, array $entityMetadata): array
    {
        $ormMetadata = [];

        $ormMetadata[$entityType] = [
            EntityParam::ATTRIBUTES => [],
            EntityParam::RELATIONS => [],
        ];

        foreach ($this->copyEntityProperties as $optionName) {
            if (isset($entityMetadata[$optionName])) {
                $ormMetadata[$entityType][$optionName] = $entityMetadata[$optionName];
            }
        }

        $ormMetadata[$entityType][EntityParam::ATTRIBUTES] = $this->convertFields($entityType, $entityMetadata);

        $ormMetadata = $this->correctFields($entityType, $ormMetadata);

        $convertedLinks = $this->convertLinks($entityType, $entityMetadata, $ormMetadata);

        $ormMetadata = Util::merge($ormMetadata, $convertedLinks);

        $this->applyFullTextSearch($ormMetadata, $entityType);
        $this->applyIndexes($ormMetadata, $entityType);

        if (!empty($entityMetadata['collection']) && is_array($entityMetadata['collection'])) {
            $collectionDefs = $entityMetadata['collection'];

            $ormMetadata[$entityType]['collection'] = [];

            if (array_key_exists('orderByColumn', $collectionDefs)) {
                $ormMetadata[$entityType]['collection']['orderBy'] = $collectionDefs['orderByColumn'];
            } else if (array_key_exists('orderBy', $collectionDefs)) {
                if (array_key_exists($collectionDefs['orderBy'], $ormMetadata[$entityType][EntityParam::ATTRIBUTES])) {
                    $ormMetadata[$entityType]['collection']['orderBy'] = $collectionDefs['orderBy'];
                }
            }

            $ormMetadata[$entityType]['collection']['order'] = 'ASC';

            if (array_key_exists('order', $collectionDefs)) {
                $ormMetadata[$entityType]['collection']['order'] = strtoupper($collectionDefs['order']);
            }
        }

        return $ormMetadata;
    }

    /**
     * @param array<string, mixed> $ormMetadata
     * @return array<string, mixed>
     */
    private function afterFieldsProcess(array $ormMetadata): array
    {
        foreach ($ormMetadata as /*$entityType =>*/ &$entityParams) {
            if (empty($entityParams[EntityParam::ATTRIBUTES])) {
                print_r($entityParams);
            }
            foreach ($entityParams[EntityParam::ATTRIBUTES] as $attribute => &$attributeParams) {

                // Remove fields without type.
                if (
                    !isset($attributeParams[AttributeParam::TYPE]) &&
                    (
                        !isset($attributeParams[AttributeParam::NOT_STORABLE]) ||
                        $attributeParams[AttributeParam::NOT_STORABLE] === false
                    )
                ) {
                    unset($entityParams[EntityParam::ATTRIBUTES][$attribute]);

                    continue;
                }

                $attributeType = $attributeParams[AttributeParam::TYPE] ?? null;

                switch ($attributeType) {
                    case Entity::ID:
                        if (empty($attributeParams[AttributeParam::DB_TYPE])) {
                            $attributeParams = array_merge($this->idParams, $attributeParams);
                        }

                        break;

                    case Entity::FOREIGN_ID:
                        $attributeParams = array_merge($this->idParams, $attributeParams);
                        $attributeParams[AttributeParam::NOT_NULL] = false;

                        break;

                    case Entity::FOREIGN_TYPE:
                        $attributeParams[AttributeParam::DB_TYPE] = Types::STRING;

                        if (empty($attributeParams[AttributeParam::LEN])) {
                            $attributeParams[AttributeParam::LEN] = $this->defaultLengthMap[Entity::VARCHAR];
                        }

                        break;

                    case Entity::BOOL:
                        $attributeParams[AttributeParam::DEFAULT] ??= false;
                        $attributeParams[AttributeParam::DEFAULT] = (bool) $attributeParams[AttributeParam::DEFAULT];

                        break;

                    case Entity::PASSWORD:
                        $attributeParams[AttributeParam::DB_TYPE] ??= Types::STRING;

                        break;

                    default:
                        $constName = strtoupper(Util::toUnderScore($attributeType));

                        if (!defined('Espo\\ORM\\Type\\AttributeType::' . $constName)) {
                            $attributeParams[AttributeParam::TYPE] = $this->defaultAttributeType;
                        }

                        break;
                }
            }
        }

        return $ormMetadata;
    }

    /**
     * @param array<string, mixed> $ormMetadata
     * @return array<string, mixed>
     */
    private function afterProcess(array $ormMetadata): array
    {
        foreach ($ormMetadata as $entityType => &$entityParams) {
            foreach ($entityParams[EntityParam::ATTRIBUTES] as $attribute => &$attributeParams) {
                $attributeType = $attributeParams[AttributeParam::TYPE] ?? null;

                switch ($attributeType) {
                    case Entity::FOREIGN:
                        $attributeParams['foreignType'] =
                            $this->obtainForeignType($ormMetadata, $entityType, $attribute);

                        break;
                }
            }
        }

        return $ormMetadata;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function obtainForeignType(array $data, string $entityType, string $attribute): ?string
    {
        $params = $data[$entityType][EntityParam::ATTRIBUTES][$attribute] ?? [];

        $foreign = $params[AttributeParam::FOREIGN] ?? null;
        $relation = $params[AttributeParam::RELATION] ?? null;

        if (!$foreign || !$relation) {
            return null;
        }

        $relationParams = $data[$entityType][EntityParam::RELATIONS][$relation] ?? [];

        $foreignEntityType = $relationParams[RelationParam::ENTITY] ?? null;

        if (!$foreignEntityType) {
            return null;
        }

        $foreignParams = $data[$foreignEntityType][EntityParam::ATTRIBUTES][$foreign] ?? [];

        return $foreignParams[AttributeParam::TYPE] ?? null;
    }

    /**
     * @param array<string, mixed> $entityMetadata
     * @return array<string, mixed>
     */
    private function convertFields(string $entityType, array &$entityMetadata): array
    {
        $entityMetadata[EntityParam::FIELDS] ??= [];

        // List of unmerged fields with default field definitions in $output.
        $unmergedFields = [Field::NAME];

        $output = [
            Attribute::ID => [
                AttributeParam::TYPE => Entity::ID,
            ],
            'name' => [
                AttributeParam::TYPE => $entityMetadata[EntityParam::FIELDS][Field::NAME][FieldParam::TYPE] ??
                    Entity::VARCHAR,
                AttributeParam::NOT_STORABLE => true,
            ],
            Attribute::DELETED => [
                AttributeParam::TYPE => Entity::BOOL,
                AttributeParam::DEFAULT => false,
            ],
        ];

        if ($entityMetadata['noDeletedAttribute'] ?? false) {
            unset($output[Attribute::DELETED]);
        }

        foreach ($entityMetadata[EntityParam::FIELDS] as $attribute => $attributeParams) {
            if (empty($attributeParams[AttributeParam::TYPE])) {
                continue;
            }

            $fieldTypeMetadata = $this->metadataHelper->getFieldDefsByType($attributeParams);

            $fieldDefs = $this->convertField($attributeParams, $fieldTypeMetadata);

            if ($fieldDefs !== false) {
                if (isset($output[$attribute]) && !in_array($attribute, $unmergedFields)) {
                    $output[$attribute] = array_merge($output[$attribute], $fieldDefs);
                } else {
                    $output[$attribute] = $fieldDefs;
                }

                /** @var array<string, array<string, mixed>> $output */
            }

            if (isset($fieldTypeMetadata['linkDefs'])) {
                $linkDefs = $this->metadataHelper->getLinkDefsInFieldMeta(
                    $entityType,
                    $attributeParams
                );

                if (isset($linkDefs)) {
                    if (!isset($entityMetadata['links'])) {
                        $entityMetadata['links'] = [];
                    }

                    $entityMetadata['links'] = Util::merge(
                        [$attribute => $linkDefs],
                        $entityMetadata['links']
                    );
                }
            }
        }

        return $output;
    }

    /**
     * Apply field converters and other corrections.
     *
     * @param array<string, mixed> $ormMetadata
     * @return array<string, mixed>
     */
    private function correctFields(string $entityType, array $ormMetadata): array
    {
        $entityMetadata = $ormMetadata[$entityType];

        foreach ($entityMetadata[EntityParam::ATTRIBUTES] as $field => $itemParams) {
            $type = $itemParams[AttributeParam::TYPE] ?? null;

            if (!$type) {
                continue;
            }

            /** @var ?class-string<FieldConverter> $className */
            $className =
                $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'converterClassName']) ??
                $this->metadata->get(['fields', $type, 'converterClassName']);

            if ($className) {
                $toUnset =
                    !in_array('', $this->metadata->get(['fields', $type, 'actualFields']) ?? []) &&
                    !in_array('', $this->metadata->get(['fields', $type, 'notActualFields']) ?? []);

                if ($toUnset) {
                    $ormMetadata = Util::unsetInArray($ormMetadata, [$entityType => ['attributes.' . $field]]);
                }

                $converter = $this->injectableFactory->create($className);

                /** @var array<string, mixed> $rawFieldDefs */
                $rawFieldDefs = $this->metadata->get(['entityDefs', $entityType, 'fields', $field]);

                $fieldDefs = FieldDefs::fromRaw($rawFieldDefs, $field);

                $convertedEntityDefs = $converter->convert($fieldDefs, $entityType);

                /** @var array<string, mixed> $ormMetadata */
                $ormMetadata = Util::merge($ormMetadata, [$entityType => $convertedEntityDefs->toAssoc()]);
            }

            $defaultAttributes = $this->metadata
                ->get(['entityDefs', $entityType, 'fields', $field, 'defaultAttributes']);

            if ($defaultAttributes && array_key_exists($field, $defaultAttributes)) {
                $defaultMetadataPart = [
                    $entityType => [
                        EntityParam::ATTRIBUTES => [
                            $field => [
                                AttributeParam::DEFAULT => $defaultAttributes[$field],
                            ]
                        ]
                    ]
                ];

                /** @var array<string, mixed> $ormMetadata */
                $ormMetadata = Util::merge($ormMetadata, $defaultMetadataPart);
            }
        }

        // @todo Refactor.
        /** @var array<string, mixed> $scopeDefs */
        $scopeDefs = $this->metadata->get(['scopes', $entityType]) ?? [];

        if ($scopeDefs['stream'] ?? false) {
            if (!isset($entityMetadata[EntityParam::FIELDS][Field::IS_FOLLOWED])) {
                $ormMetadata[$entityType][EntityParam::ATTRIBUTES][Field::IS_FOLLOWED] = [
                    AttributeParam::TYPE => Entity::BOOL,
                    AttributeParam::NOT_STORABLE => true,
                    CoreAttributeParam::NOT_EXPORTABLE => true,
                ];

                $ormMetadata[$entityType][EntityParam::ATTRIBUTES][Field::FOLLOWERS . 'Ids'] = [
                    AttributeParam::TYPE => Entity::JSON_ARRAY,
                    AttributeParam::NOT_STORABLE => true,
                    CoreAttributeParam::NOT_EXPORTABLE => true,
                ];

                $ormMetadata[$entityType][EntityParam::ATTRIBUTES][Field::FOLLOWERS . 'Names'] = [
                    AttributeParam::TYPE => Entity::JSON_OBJECT,
                    AttributeParam::NOT_STORABLE => true,
                    CoreAttributeParam::NOT_EXPORTABLE => true,
                ];
            }
        }

        // @todo Refactor.
        if ($scopeDefs['stars'] ?? false) {
            if (!isset($entityMetadata[EntityParam::FIELDS][Field::IS_STARRED])) {
                $ormMetadata[$entityType][EntityParam::ATTRIBUTES][Field::IS_STARRED] = [
                    AttributeParam::TYPE => Entity::BOOL,
                    AttributeParam::NOT_STORABLE => true,
                    CoreAttributeParam::NOT_EXPORTABLE => true,
                    'readOnly' => true,
                ];
            }
        }

        // @todo Refactor.
        if ($this->metadata->get(['entityDefs', $entityType, 'optimisticConcurrencyControl'])) {
            $ormMetadata[$entityType][EntityParam::ATTRIBUTES][Field::VERSION_NUMBER] = [
                AttributeParam::TYPE => Entity::INT,
                AttributeParam::DB_TYPE => Types::BIGINT,
                CoreAttributeParam::NOT_EXPORTABLE => true,
            ];
        }

        return $ormMetadata;
    }

    /**
     * @param array<string, mixed> $fieldParams
     * @param ?array<string, mixed> $fieldTypeMetadata
     * @return array<string, mixed>|false
     */
    private function convertField(
        array $fieldParams,
        ?array $fieldTypeMetadata = null
    ) {
        if (!isset($fieldTypeMetadata)) {
            $fieldTypeMetadata = $this->metadataHelper->getFieldDefsByType($fieldParams);
        }

        $this->prepareFieldParamsBeforeConvert($fieldParams);

        if (isset($fieldTypeMetadata['fieldDefs'])) {
            /** @var array<string, mixed> $fieldParams */
            $fieldParams = Util::merge($fieldParams, $fieldTypeMetadata['fieldDefs']);
        }

        if ($fieldParams[FieldParam::TYPE] == self::FIELD_TYPE_BASE && isset($fieldParams[FieldParam::DB_TYPE])) {
            $fieldParams[FieldParam::NOT_STORABLE] = false;
        }

        if (!empty($fieldTypeMetadata['skipOrmDefs']) || !empty($fieldParams['skipOrmDefs'])) {
            return false;
        }

        if (
            isset($fieldParams[FieldParam::NOT_NULL]) && !$fieldParams[FieldParam::NOT_NULL] &&
            isset($fieldParams['required']) && $fieldParams['required']
        ) {
            unset($fieldParams[FieldParam::NOT_NULL]);
        }

        $fieldDefs = $this->getInitValues($fieldParams);

        if (isset($fieldParams['db']) && $fieldParams['db'] === false) {
            $fieldDefs[AttributeParam::NOT_STORABLE] = true;
        }

        $type = $fieldDefs[FieldParam::TYPE] ?? null;

        if (
            $type &&
            !isset($fieldDefs[AttributeParam::LEN]) &&
            array_key_exists($type, $this->defaultLengthMap)
        ) {
            $fieldDefs[AttributeParam::LEN] = $this->defaultLengthMap[$type];
        }

        return $fieldDefs;
    }

    /**
     * @param array<string, mixed> $fieldParams
     */
    private function prepareFieldParamsBeforeConvert(array &$fieldParams): void
    {
        $type = $fieldParams[FieldParam::TYPE] ?? null;

        if ($type === FieldType::ENUM) {
            if (($fieldParams[FieldParam::DEFAULT] ?? null) === '') {
                $fieldParams[FieldParam::DEFAULT] = null;
            }
        }
    }

    /**
     * @param array<string, mixed> $entityMetadata
     * @param array<string, mixed> $ormMetadata
     * @return array<string, mixed>
     */
    private function convertLinks(string $entityType, array $entityMetadata, array $ormMetadata): array
    {
        if (!isset($entityMetadata['links'])) {
            return [];
        }

        $relationships = [];

        foreach ($entityMetadata['links'] as $linkName => $linkParams) {
            if (isset($linkParams['skipOrmDefs']) && $linkParams['skipOrmDefs'] === true) {
                continue;
            }

            $convertedLink = $this->relationConverter->process($linkName, $linkParams, $entityType);

            if ($convertedLink) {
                /** @var array<string, mixed> $relationships */
                $relationships = Util::merge($convertedLink, $relationships);
            }
        }

        return $relationships;
    }

    /**
     * @param array<string, mixed> $attributeParams
     * @return array<string, mixed>
     */
    private function getInitValues(array $attributeParams)
    {
        $values = [];

        foreach ($this->paramMap as $espoType => $ormType) {
            if (!array_key_exists($espoType, $attributeParams)) {
                continue;
            }

            switch ($espoType) {
                case AttributeParam::DEFAULT:
                    if (
                        is_null($attributeParams[$espoType]) ||
                        is_array($attributeParams[$espoType]) ||
                        !preg_match('/^javascript:/i', $attributeParams[$espoType])
                    ) {
                        $values[$ormType] = $attributeParams[$espoType];
                    }

                    break;

                default:
                    $values[$ormType] = $attributeParams[$espoType];

                    break;
            }
        }

        if (isset($attributeParams[AttributeParam::TYPE])) {
            $values['fieldType'] = $attributeParams[AttributeParam::TYPE];
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $ormMetadata
     */
    private function applyFullTextSearch(array &$ormMetadata, string $entityType): void
    {
        if (!$this->metadata->get(['entityDefs', $entityType, 'collection', 'fullTextSearch'])) {
            return;
        }

        $fieldList = $this->metadata
            ->get(['entityDefs', $entityType, 'collection', 'textFilterFields'], [Field::NAME]);

        $fullTextSearchColumnList = [];

        foreach ($fieldList as $field) {
            $defs = $this->metadata->get(['entityDefs', $entityType, 'fields', $field], []);

            if (empty($defs[FieldParam::TYPE])) {
                continue;
            }

            $fieldType = $defs[FieldParam::TYPE];

            if (!empty($defs[FieldParam::NOT_STORABLE])) {
                continue;
            }

            if (!$this->metadata->get(['fields', $fieldType, 'fullTextSearch'])) {
                continue;
            }

            $partList = $this->metadata->get(['fields', $fieldType, 'fullTextSearchColumnList']);

            if ($partList) {
                if ($this->metadata->get(['fields', $fieldType, 'naming']) === 'prefix') {
                    foreach ($partList as $part) {
                        $fullTextSearchColumnList[] = $part . ucfirst($field);
                    }
                } else {
                    foreach ($partList as $part) {
                        $fullTextSearchColumnList[] = $field . ucfirst($part);
                    }
                }
            } else {
                $fullTextSearchColumnList[] = $field;
            }
        }

        if (!empty($fullTextSearchColumnList)) {
            $ormMetadata[$entityType]['fullTextSearchColumnList'] = $fullTextSearchColumnList;

            if (!array_key_exists(EntityParam::INDEXES, $ormMetadata[$entityType])) {
                $ormMetadata[$entityType][EntityParam::INDEXES] = [];
            }

            $ormMetadata[$entityType][EntityParam::INDEXES]['system_fullTextSearch'] = [
                IndexParam::COLUMNS => $fullTextSearchColumnList,
                IndexParam::FLAGS => ['fulltext']
            ];
        }
    }

    /**
     * @param array<string, mixed> $ormMetadata
     */
    private function applyIndexes(array &$ormMetadata, string $entityType): void
    {
        $defs = &$ormMetadata[$entityType];

        $defs[EntityParam::INDEXES] ??= [];

        if (isset($defs[EntityParam::ATTRIBUTES])) {
            $indexList = self::getEntityIndexListFromAttributes($defs[EntityParam::ATTRIBUTES]);

            foreach ($indexList as $indexName => $indexParams) {
                if (!isset($defs[EntityParam::INDEXES][$indexName])) {
                    $defs[EntityParam::INDEXES][$indexName] = $indexParams;
                }
            }
        }

        foreach ($defs[EntityParam::INDEXES] as $indexName => &$indexData) {
            $indexDefs = IndexDefs::fromRaw($indexData, $indexName);

            if (!$indexDefs->getKey()) {
                $indexData[IndexParam::KEY] = $this->composeIndexKey($indexDefs, $entityType);
            }
        }

        if (isset($defs[EntityParam::RELATIONS])) {
            foreach ($defs[EntityParam::RELATIONS] as &$relationData) {
                $type = $relationData[RelationParam::TYPE] ?? null;

                if ($type !== Entity::MANY_MANY) {
                    continue;
                }

                $relationName = $relationData[RelationParam::RELATION_NAME] ?? '';

                $relationData[RelationParam::INDEXES] ??= [];

                $uniqueColumnList = [];

                foreach (($relationData[RelationParam::MID_KEYS] ?? []) as $midKey) {
                    $indexName = $midKey;

                    $indexDefs = IndexDefs::fromRaw([IndexParam::COLUMNS => [$midKey]], $indexName);

                    $relationData[RelationParam::INDEXES][$indexName] = [
                        IndexParam::COLUMNS => $indexDefs->getColumnList(),
                        IndexParam::KEY => $this->composeIndexKey($indexDefs, ucfirst($relationName)),
                    ];

                    $uniqueColumnList[] = $midKey;
                }

                foreach ($relationData[RelationParam::INDEXES] as $indexName => &$indexData) {
                    if (!empty($indexData[IndexParam::KEY])) {
                        continue;
                    }

                    $indexDefs = IndexDefs::fromRaw($indexData, $indexName);

                    $indexData[IndexParam::KEY] = $this->composeIndexKey($indexDefs, ucfirst($relationName));
                }

                foreach (($relationData[RelationParam::CONDITIONS] ?? []) as $column => $fieldParams) {
                    $uniqueColumnList[] = $column;
                }

                if ($uniqueColumnList !== []) {
                    $indexName = implode('_', $uniqueColumnList);

                    $indexDefs = IndexDefs
                        ::fromRaw([
                            IndexParam::TYPE => self::INDEX_TYPE_UNIQUE,
                            IndexParam::COLUMNS => $uniqueColumnList,
                        ], $indexName);

                    $relationData[RelationParam::INDEXES][$indexName] = [
                        IndexParam::TYPE => self::INDEX_TYPE_UNIQUE,
                        IndexParam::COLUMNS => $indexDefs->getColumnList(),
                        IndexParam::KEY => $this->composeIndexKey($indexDefs, ucfirst($relationName)),
                    ];
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $defs
     * @return array<string, mixed>
     */
    private function obtainAdditionalTablesOrmMetadata(array $defs): array
    {
        /** @var array<string, array<string, mixed>> $additionalDefs */
        $additionalDefs = $defs['additionalTables'] ?? [];

        if ($additionalDefs === []) {
            return [];
        }

        /** @var string[] $entityTypeList */
        $entityTypeList = array_keys($additionalDefs);

        foreach ($entityTypeList as $itemEntityType) {
            $this->applyIndexes($additionalDefs, $itemEntityType);
        }

        // For backward compatibility. Actual as of v8.0.
        // @todo Remove in v10.0.
        // @todo Add deprecation warning in v9.0. If 'fields' is set.
        foreach ($additionalDefs as &$entityDefs) {
            if (!isset($entityDefs['attributes'])) {
                $entityDefs['attributes'] = $entityDefs['fields'] ?? [];

                unset($entityDefs['fields']);
            }
        }

        return $additionalDefs;
    }

    /**
     * @param array<string, mixed> $defs
     * @return array<string, mixed>
     */
    private function createEntityTypesFromRelations(string $entityType, array $defs): array
    {
        $result = [];

        foreach ($defs[EntityParam::RELATIONS] as $name => $relationParams) {
            $relationDefs = RelationDefs::fromRaw($relationParams, $name);

            if ($relationDefs->getType() !== Entity::MANY_MANY) {
                continue;
            }

            $relationEntityType = ucfirst($relationDefs->getRelationshipName());

            $itemDefs = [
                'skipRebuild' => true,
                EntityParam::ATTRIBUTES => [
                    Attribute::ID => [
                        AttributeParam::TYPE => Entity::ID,
                        'autoincrement' => true,
                        AttributeParam::DB_TYPE => Types::BIGINT, // ignored because of `skipRebuild`
                    ],
                    Attribute::DELETED => [
                        AttributeParam::TYPE => Entity::BOOL,
                    ],
                ],
            ];

            if (!$relationDefs->hasMidKey()) {
                throw new LogicException(
                    "Bad manyMany relation $name in $entityType. Might be not defined on the other side.");
            }

            $key1 = $relationDefs->getMidKey();
            $key2 = $relationDefs->getForeignMidKey();

            $midKeys = [$key1, $key2];

            foreach ($midKeys as $key) {
                $itemDefs[EntityParam::ATTRIBUTES][$key] = [
                    AttributeParam::TYPE => Entity::FOREIGN_ID,
                ];
            }

            foreach ($relationDefs->getParam(RelationParam::ADDITIONAL_COLUMNS) ?? [] as $columnName => $columnItem) {
                $columnItem[AttributeParam::TYPE] ??= Entity::VARCHAR;

                $attributeDefs = AttributeDefs::fromRaw($columnItem, $columnName);

                $columnDefs = [
                    AttributeParam::TYPE => $attributeDefs->getType(),
                ];

                if ($attributeDefs->getLength()) {
                    $columnDefs[AttributeParam::LEN] = $attributeDefs->getLength();
                }

                if ($attributeDefs->getParam(AttributeParam::DEFAULT) !== null) {
                    $columnDefs[AttributeParam::DEFAULT] = $attributeDefs->getParam(AttributeParam::DEFAULT);
                }

                $itemDefs[EntityParam::ATTRIBUTES][$columnName] = $columnDefs;
            }

            foreach ($relationDefs->getIndexList() as $indexDefs) {
                $itemDefs[EntityParam::INDEXES] ??= [];
                $itemDefs[EntityParam::INDEXES][] = self::convertIndexDefsToRaw($indexDefs);
            }

            $result[$relationEntityType] = $itemDefs;
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private static function convertIndexDefsToRaw(IndexDefs $indexDefs): array
    {
        return [
            IndexParam::TYPE => $indexDefs->isUnique() ? self::INDEX_TYPE_UNIQUE : self::INDEX_TYPE_INDEX,
            IndexParam::COLUMNS => $indexDefs->getColumnList(),
            IndexParam::FLAGS => $indexDefs->getFlagList(),
            IndexParam::KEY => $indexDefs->getKey(),
        ];
    }

    /**
     * @param array<string, mixed> $attributesMetadata
     * @return array<string, mixed>
     */
    private static function getEntityIndexListFromAttributes(array $attributesMetadata): array
    {
        $indexList = [];

        foreach ($attributesMetadata as $attributeName => $rawParams) {
            $attributeDefs = AttributeDefs::fromRaw($rawParams, $attributeName);

            if ($attributeDefs->isNotStorable()) {
                continue;
            }

            $indexType = self::getIndexTypeByAttributeDefs($attributeDefs);
            $indexName = self::getIndexNameByAttributeDefs($attributeDefs);

            if (!$indexType || !$indexName) {
                continue;
            }

            $keyValue = $attributeDefs->getParam($indexType);

            if ($keyValue === true) {
                $indexList[$indexName][IndexParam::TYPE] = $indexType;
                $indexList[$indexName][IndexParam::COLUMNS] = [$attributeName];
            } else if (is_string($keyValue)) {
                $indexList[$indexName][IndexParam::TYPE] = $indexType;
                $indexList[$indexName][IndexParam::COLUMNS][] = $attributeName;
            }
        }

        return $indexList;
    }


    private static function getIndexTypeByAttributeDefs(AttributeDefs $attributeDefs): ?string
    {
        if (
            $attributeDefs->getType() !== Entity::ID &&
            $attributeDefs->getParam(self::INDEX_TYPE_UNIQUE)
        ) {
            return self::INDEX_TYPE_UNIQUE;
        }

        if ($attributeDefs->getParam(self::INDEX_TYPE_INDEX)) {
            return self::INDEX_TYPE_INDEX;
        }

        return null;
    }

    private static function getIndexNameByAttributeDefs(AttributeDefs $attributeDefs): ?string
    {
        $indexType = self::getIndexTypeByAttributeDefs($attributeDefs);

        if (!$indexType) {
            return null;
        }

        $keyValue = $attributeDefs->getParam($indexType);

        if ($keyValue === true) {
            return $attributeDefs->getName();
        }

        if (is_string($keyValue)) {
            return $keyValue;
        }

        return null;
    }
}
