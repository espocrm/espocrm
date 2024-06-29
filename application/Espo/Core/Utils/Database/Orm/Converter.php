<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\Core\ORM\Defs\AttributeParam;
use Espo\Core\Utils\Database\ConfigDataProvider;
use Espo\Core\Utils\Database\MetadataProvider;
use Espo\Core\Utils\Util;
use Espo\ORM\Defs\AttributeDefs;
use Espo\ORM\Defs\FieldDefs;
use Espo\ORM\Defs\IndexDefs;
use Espo\ORM\Defs\RelationDefs;
use Espo\ORM\Entity;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Metadata\Helper as MetadataHelper;
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

    /**
     * Mapping entityDefs => ORM.
     *
     * @var array<string, string>
     */
    private array $paramMap = [
        'type' => 'type',
        'dbType' => 'dbType',
        'maxLength' => 'len',
        'len' => 'len',
        'notNull' => 'notNull',
        'exportDisabled' => AttributeParam::NOT_EXPORTABLE,
        'autoincrement' => 'autoincrement',
        'entity' => 'entity',
        'notStorable' => 'notStorable',
        'link' => 'relation',
        'field' => 'foreign',  // @todo change "foreign" to "field"
        'unique' => 'unique',
        'index' => 'index',
        'default' => 'default',
        'select' => 'select',
        'order' => 'order',
        'where' => 'where',
        'storeArrayValues' => 'storeArrayValues',
        'binary' => 'binary',
        'dependeeAttributeList' => 'dependeeAttributeList',
        'precision' => 'precision',
        'scale' => 'scale',
    ];

    /** @var array<string, mixed> */
    private array $idParams = [];

    /** @var string[] */
    private array $copyEntityProperties = ['indexes'];

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

        $this->idParams['len'] = $metadataProvider->getIdLength();
        $this->idParams['dbType'] = $metadataProvider->getIdDbType();
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
            'attributes' => [],
            'relations' => [],
        ];

        foreach ($this->copyEntityProperties as $optionName) {
            if (isset($entityMetadata[$optionName])) {
                $ormMetadata[$entityType][$optionName] = $entityMetadata[$optionName];
            }
        }

        $ormMetadata[$entityType]['attributes'] = $this->convertFields($entityType, $entityMetadata);

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
            }
            else if (array_key_exists('orderBy', $collectionDefs)) {
                if (array_key_exists($collectionDefs['orderBy'], $ormMetadata[$entityType]['attributes'])) {
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
            if (empty($entityParams['attributes'])) {
                print_r($entityParams);
            }
            foreach ($entityParams['attributes'] as $attribute => &$attributeParams) {

                // Remove fields without type.
                if (
                    !isset($attributeParams['type']) &&
                    (!isset($attributeParams['notStorable']) || $attributeParams['notStorable'] === false)
                ) {
                    unset($entityParams['attributes'][$attribute]);

                    continue;
                }

                $attributeType = $attributeParams['type'] ?? null;

                switch ($attributeType) {
                    case Entity::ID:
                        if (empty($attributeParams['dbType'])) {
                            $attributeParams = array_merge($this->idParams, $attributeParams);
                        }

                        break;

                    case Entity::FOREIGN_ID:
                        $attributeParams = array_merge($this->idParams, $attributeParams);
                        $attributeParams['notNull'] = false;

                        break;

                    case Entity::FOREIGN_TYPE:
                        $attributeParams['dbType'] = Types::STRING;

                        if (empty($attributeParams['len'])) {
                            $attributeParams['len'] = $this->defaultLengthMap[Entity::VARCHAR];
                        }

                        break;

                    case Entity::BOOL:
                        $attributeParams['default'] ??= false;
                        $attributeParams['default'] = (bool) $attributeParams['default'];

                        break;

                    case Entity::PASSWORD:
                        $attributeParams['dbType'] ??= Types::STRING;

                        break;

                    default:
                        $constName = strtoupper(Util::toUnderScore($attributeType));

                        if (!defined('Espo\\ORM\\Type\\AttributeType::' . $constName)) {
                            $attributeParams['type'] = $this->defaultAttributeType;
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
            foreach ($entityParams['attributes'] as $attribute => &$attributeParams) {
                $attributeType = $attributeParams['type'] ?? null;

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
        $params = $data[$entityType]['attributes'][$attribute] ?? [];

        $foreign = $params['foreign'] ?? null;
        $relation = $params['relation'] ?? null;

        if (!$foreign || !$relation) {
            return null;
        }

        $relationParams = $data[$entityType]['relations'][$relation] ?? [];

        $foreignEntityType = $relationParams['entity'] ?? null;

        if (!$foreignEntityType) {
            return null;
        }

        $foreignParams = $data[$foreignEntityType]['attributes'][$foreign] ?? [];

        return $foreignParams['type'] ?? null;
    }

    /**
     * @param array<string, mixed> $entityMetadata
     * @return array<string, mixed>
     */
    private function convertFields(string $entityType, array &$entityMetadata): array
    {
        $entityMetadata['fields'] ??= [];

        // List of unmerged fields with default field definitions in $output.
        $unmergedFields = [
            'name',
        ];

        $output = [
            'id' => [
                'type' => Entity::ID,
            ],
            'name' => [
                'type' => $entityMetadata['fields']['name']['type'] ?? Entity::VARCHAR,
                'notStorable' => true,
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => false,
            ],
        ];

        if ($entityMetadata['noDeletedAttribute'] ?? false) {
            unset($output['deleted']);
        }

        foreach ($entityMetadata['fields'] as $attribute => $attributeParams) {
            if (empty($attributeParams['type'])) {
                continue;
            }

            $fieldTypeMetadata = $this->metadataHelper->getFieldDefsByType($attributeParams);

            $fieldDefs = $this->convertField($attributeParams, $fieldTypeMetadata);

            if ($fieldDefs !== false) {
                if (isset($output[$attribute]) && !in_array($attribute, $unmergedFields)) {
                    $output[$attribute] = array_merge($output[$attribute], $fieldDefs);
                }
                else {
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

        foreach ($entityMetadata['attributes'] as $field => $itemParams) {
            $type = $itemParams['type'] ?? null;

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
                        'attributes' => [
                            $field => [
                                'default' => $defaultAttributes[$field],
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
            if (!isset($entityMetadata['fields']['isFollowed'])) {
                $ormMetadata[$entityType]['attributes']['isFollowed'] = [
                    'type' => Entity::BOOL,
                    'notStorable' => true,
                    AttributeParam::NOT_EXPORTABLE => true,
                ];

                $ormMetadata[$entityType]['attributes']['followersIds'] = [
                    'type' => Entity::JSON_ARRAY,
                    'notStorable' => true,
                    AttributeParam::NOT_EXPORTABLE => true,
                ];

                $ormMetadata[$entityType]['attributes']['followersNames'] = [
                    'type' => Entity::JSON_OBJECT,
                    'notStorable' => true,
                    AttributeParam::NOT_EXPORTABLE => true,
                ];
            }
        }

        // @todo Refactor.
        if ($scopeDefs['stars'] ?? false) {
            if (!isset($entityMetadata['fields']['isStarred'])) {
                $ormMetadata[$entityType]['attributes']['isStarred'] = [
                    'type' => Entity::BOOL,
                    'notStorable' => true,
                    AttributeParam::NOT_EXPORTABLE => true,
                    'readOnly' => true,
                ];
            }
        }

        // @todo Refactor.
        if ($this->metadata->get(['entityDefs', $entityType, 'optimisticConcurrencyControl'])) {
            $ormMetadata[$entityType]['attributes']['versionNumber'] = [
                'type' => Entity::INT,
                'dbType' => Types::BIGINT,
                AttributeParam::NOT_EXPORTABLE => true,
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

        if ($fieldParams['type'] == 'base' && isset($fieldParams['dbType'])) {
            $fieldParams['notStorable'] = false;
        }

        if (!empty($fieldTypeMetadata['skipOrmDefs']) || !empty($fieldParams['skipOrmDefs'])) {
            return false;
        }

        if (
            isset($fieldParams['notNull']) && !$fieldParams['notNull'] &&
            isset($fieldParams['required']) && $fieldParams['required']
        ) {
            unset($fieldParams['notNull']);
        }

        $fieldDefs = $this->getInitValues($fieldParams);

        if (isset($fieldParams['db']) && $fieldParams['db'] === false) {
            $fieldDefs['notStorable'] = true;
        }

        $type = $fieldDefs['type'] ?? null;

        if (
            $type &&
            !isset($fieldDefs['len']) &&
            array_key_exists($type, $this->defaultLengthMap)
        ) {
            $fieldDefs['len'] = $this->defaultLengthMap[$type];
        }

        return $fieldDefs;
    }

    /**
     * @param array<string, mixed> $fieldParams
     */
    private function prepareFieldParamsBeforeConvert(array &$fieldParams): void
    {
        $type = $fieldParams['type'] ?? null;

        if ($type === 'enum') {
            if (($fieldParams['default'] ?? null) === '') {
                $fieldParams['default'] = null;
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

            $convertedLink = $this->relationConverter->process($linkName, $linkParams, $entityType, $ormMetadata);

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
                case 'default':
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

        if (isset($attributeParams['type'])) {
            $values['fieldType'] = $attributeParams['type'];
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
            ->get(['entityDefs', $entityType, 'collection', 'textFilterFields'], ['name']);

        $fullTextSearchColumnList = [];

        foreach ($fieldList as $field) {
            $defs = $this->metadata->get(['entityDefs', $entityType, 'fields', $field], []);

            if (empty($defs['type'])) {
                continue;
            }

            $fieldType = $defs['type'];

            if (!empty($defs['notStorable'])) {
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
                }
                else {
                    foreach ($partList as $part) {
                        $fullTextSearchColumnList[] = $field . ucfirst($part);
                    }
                }
            }
            else {
                $fullTextSearchColumnList[] = $field;
            }
        }

        if (!empty($fullTextSearchColumnList)) {
            $ormMetadata[$entityType]['fullTextSearchColumnList'] = $fullTextSearchColumnList;

            if (!array_key_exists('indexes', $ormMetadata[$entityType])) {
                $ormMetadata[$entityType]['indexes'] = [];
            }

            $ormMetadata[$entityType]['indexes']['system_fullTextSearch'] = [
                'columns' => $fullTextSearchColumnList,
                'flags' => ['fulltext']
            ];
        }
    }

    /**
     * @param array<string, mixed> $ormMetadata
     */
    private function applyIndexes(array &$ormMetadata, string $entityType): void
    {
        $defs = &$ormMetadata[$entityType];

        $defs['indexes'] ??= [];

        if (isset($defs['attributes'])) {
            $indexList = self::getEntityIndexListFromAttributes($defs['attributes']);

            foreach ($indexList as $indexName => $indexParams) {
                if (!isset($defs['indexes'][$indexName])) {
                    $defs['indexes'][$indexName] = $indexParams;
                }
            }
        }

        foreach ($defs['indexes'] as $indexName => &$indexData) {
            $indexDefs = IndexDefs::fromRaw($indexData, $indexName);

            if (!$indexDefs->getKey()) {
                $indexData['key'] = $this->composeIndexKey($indexDefs, $entityType);
            }
        }

        if (isset($defs['relations'])) {
            foreach ($defs['relations'] as &$relationData) {
                $type = $relationData['type'] ?? null;

                if ($type !== Entity::MANY_MANY) {
                    continue;
                }

                $relationName = $relationData['relationName'] ?? '';

                $relationData['indexes'] ??= [];

                $uniqueColumnList = [];

                foreach (($relationData['midKeys'] ?? []) as $midKey) {
                    $indexName = $midKey;

                    $indexDefs = IndexDefs::fromRaw(['columns' => [$midKey]], $indexName);

                    $relationData['indexes'][$indexName] = [
                        'columns' => $indexDefs->getColumnList(),
                        'key' => $this->composeIndexKey($indexDefs, ucfirst($relationName)),
                    ];

                    $uniqueColumnList[] = $midKey;
                }

                foreach ($relationData['indexes'] as $indexName => &$indexData) {
                    if (!empty($indexData['key'])) {
                        continue;
                    }

                    $indexDefs = IndexDefs::fromRaw($indexData, $indexName);

                    $indexData['key'] = $this->composeIndexKey($indexDefs, ucfirst($relationName));
                }

                foreach (($relationData['conditions'] ?? []) as $column => $fieldParams) {
                    $uniqueColumnList[] = $column;
                }

                if ($uniqueColumnList !== []) {
                    $indexName = implode('_', $uniqueColumnList);

                    $indexDefs = IndexDefs
                        ::fromRaw([
                            'columns' => $uniqueColumnList,
                            'type' => self::INDEX_TYPE_UNIQUE,
                        ], $indexName);

                    $relationData['indexes'][$indexName] = [
                        'type' => self::INDEX_TYPE_UNIQUE,
                        'columns' => $indexDefs->getColumnList(),
                        'key' => $this->composeIndexKey($indexDefs, ucfirst($relationName)),
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

        foreach ($defs['relations'] as $name => $relationParams) {
            $relationDefs = RelationDefs::fromRaw($relationParams, $name);

            if ($relationDefs->getType() !== Entity::MANY_MANY) {
                continue;
            }

            $relationEntityType = ucfirst($relationDefs->getRelationshipName());

            $itemDefs = [
                'skipRebuild' => true,
                'attributes' => [
                    'id' => [
                        'type' => Entity::ID,
                        'autoincrement' => true,
                        'dbType' => Types::BIGINT, // ignored because of `skipRebuild`
                    ],
                    'deleted' => [
                        'type' => Entity::BOOL,
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
                $itemDefs['attributes'][$key] = [
                    'type' => Entity::FOREIGN_ID,
                ];
            }

            foreach ($relationDefs->getParam('additionalColumns') ?? [] as $columnName => $columnItem) {
                $columnItem['type'] ??= Entity::VARCHAR;

                $attributeDefs = AttributeDefs::fromRaw($columnItem, $columnName);

                $columnDefs = [
                    'type' => $attributeDefs->getType(),
                ];

                if ($attributeDefs->getLength()) {
                    $columnDefs['len'] = $attributeDefs->getLength();
                }

                if ($attributeDefs->getParam('default') !== null) {
                    $columnDefs['default'] = $attributeDefs->getParam('default');
                }

                $itemDefs['attributes'][$columnName] = $columnDefs;
            }

            foreach ($relationDefs->getIndexList() as $indexDefs) {
                $itemDefs['indexes'] ??= [];
                $itemDefs['indexes'][] = self::convertIndexDefsToRaw($indexDefs);
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
            'type' => $indexDefs->isUnique() ? self::INDEX_TYPE_UNIQUE : self::INDEX_TYPE_INDEX,
            'columns' => $indexDefs->getColumnList(),
            'flags' => $indexDefs->getFlagList(),
            'key' => $indexDefs->getKey(),
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
                $indexList[$indexName]['type'] = $indexType;
                $indexList[$indexName]['columns'] = [$attributeName];
            }
            else if (is_string($keyValue)) {
                $indexList[$indexName]['type'] = $indexType;
                $indexList[$indexName]['columns'][] = $attributeName;
            }
        }

        /** @var array<string, mixed> */
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
