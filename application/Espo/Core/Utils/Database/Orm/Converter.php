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

namespace Espo\Core\Utils\Database\Orm;

use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\Core\Utils\Database\Schema\Utils as SchemaUtils;

use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Config;

use Espo\Core\Utils\Metadata\Helper as MetadataHelper;
use Espo\Core\Utils\Database\Helper as DatabaseHelper;

class Converter
{
    private $metadata;

    private $fileManager;

    private $config;

    private $metadataHelper;

    private $databaseHelper;

    private $relationManager;

    private $entityDefs;

    protected $defaultFieldType = 'varchar';

    protected $defaultNaming = 'postfix';

    protected $defaultLength = [
        'varchar' => 255,
        'int' => 11,
    ];

    protected $defaultValue = [
        'bool' => false,
    ];

    /**
     * params mapping: entityDefs => ORM
     */
    protected $fieldAccordances = [
        'type' => 'type',
        'dbType' => 'dbType',
        'maxLength' => 'len',
        'len' => 'len',
        'notNull' => 'notNull',
        'exportDisabled' => 'notExportable',
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
    ];

    protected $idParams = [
        'dbType' => 'varchar',
        'len' => 24,
    ];

    /**
     * Permitted entityDefs parameters which will be copied to ormMetadata.
     */
    protected $permittedEntityOptions = [
        'indexes',
        'additionalTables',
    ];

    public function __construct(Metadata $metadata, FileManager $fileManager, Config $config = null)
    {
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->config = $config;

        $this->relationManager = new RelationManager($this->metadata, $config);

        $this->metadataHelper = new MetadataHelper($this->metadata);
        $this->databaseHelper = new DatabaseHelper($this->config);
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getEntityDefs($reload = false)
    {
        if (empty($this->entityDefs) || $reload) {
            $this->entityDefs = $this->getMetadata()->get('entityDefs');
        }

        return $this->entityDefs;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getRelationManager()
    {
        return $this->relationManager;
    }

    protected function getMetadataHelper()
    {
        return $this->metadataHelper;
    }

    protected function getDatabaseHelper()
    {
        return $this->databaseHelper;
    }

    /**
     * Covert metadata > entityDefs to ORM metadata.
     */
    public function process() : array
    {
        $entityDefs = $this->getEntityDefs(true);

        $ormMetadata = [];

        foreach ($entityDefs as $entityType => $entityMetadata) {
            if (empty($entityMetadata)) {
                $GLOBALS['log']->critical(
                    'Orm\Converter:process(), Entity:'.$entityType.' - metadata cannot be converted into ORM format'
                );

                continue;
            }

            if ($entityMetadata['skipRebuild'] ?? false) {
                $ormMetadata[$entityType]['skipRebuild'] = true;
            }

            $ormMetadata = Util::merge($ormMetadata, $this->convertEntity($entityType, $entityMetadata));
        }

        $ormMetadata = $this->afterProcess($ormMetadata);

        foreach ($ormMetadata as $entityType => $entityOrmMetadata) {
            $ormMetadata = Util::merge(
                $ormMetadata,
                $this->createRelationsEntityDefs($entityType, $entityOrmMetadata)
            );

            $ormMetadata = Util::merge(
                $ormMetadata,
                $this->createAdditionalEntityTypes($entityType, $entityOrmMetadata)
            );
        }

        return $ormMetadata;
    }

    protected function convertEntity(string $entityType, array $entityMetadata) : array
    {
        $ormMetadata = [];
        $ormMetadata[$entityType] = [
            'fields' => [],
            'relations' => [],
        ];

        foreach ($this->permittedEntityOptions as $optionName) {
            if (isset($entityMetadata[$optionName])) {
                $ormMetadata[$entityType][$optionName] = $entityMetadata[$optionName];
            }
        }

        $ormMetadata[$entityType]['fields'] = $this->convertFields($entityType, $entityMetadata);

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
                if (array_key_exists($collectionDefs['orderBy'], $ormMetadata[$entityType]['fields'])) {
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

    public function afterProcess(array $ormMetadata) : array
    {
        foreach ($ormMetadata as $entityType => &$entityParams) {
            foreach ($entityParams['fields'] as $fieldName => &$fieldParams) {

                /* remove fields without type */
                if (
                    !isset($fieldParams['type']) &&
                    (!isset($fieldParams['notStorable']) || $fieldParams['notStorable'] === false)
                ) {
                    unset($entityParams['fields'][$fieldName]);

                    continue;
                }

                switch ($fieldParams['type']) {
                    case 'id':
                        if ($fieldParams['dbType'] != 'int') {
                            $fieldParams = array_merge($this->idParams, $fieldParams);
                        }

                        break;

                    case 'foreignId':
                        $fieldParams = array_merge($this->idParams, $fieldParams);
                        $fieldParams['notNull'] = false;

                        break;

                    case 'foreignType':
                        $fieldParams['dbType'] = Entity::VARCHAR;
                        if (empty($fieldParams['len'])) {
                            $fieldParams['len'] = $this->defaultLength['varchar'];
                        }

                        break;

                    case 'bool':
                        $fieldParams['default'] = isset($fieldParams['default']) ?
                            (bool) $fieldParams['default'] :
                            $this->defaultValue['bool'];

                        break;

                    default:
                        $constName = strtoupper(Util::toUnderScore($fieldParams['type']));

                        if (!defined('Espo\\ORM\\Entity::' . $constName)) {
                            $fieldParams['type'] = $this->defaultFieldType;
                        }

                        break;
                }
            }
        }

        return $ormMetadata;
    }


    protected function convertFields(string $entityType, array &$entityMetadata) : array
    {
        // List of unmerged fields with default field definitions in $output.
        $unmergedFields = [
            'name',
        ];

        $output = [
            'id' => [
                'type' => Entity::ID,
                'dbType' => 'varchar',
            ],
            'name' => [
                'type' => isset($entityMetadata['fields']['name']['type']) ?
                    $entityMetadata['fields']['name']['type'] : Entity::VARCHAR,
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

        foreach ($entityMetadata['fields'] as $fieldName => $fieldParams) {
            if (empty($fieldParams['type'])) continue;

            $fieldTypeMetadata = $this->getMetadataHelper()->getFieldDefsByType($fieldParams);

            $fieldDefs = $this->convertField($entityType, $fieldName, $fieldParams, $fieldTypeMetadata);

            if ($fieldDefs !== false) {
                if (isset($output[$fieldName]) && !in_array($fieldName, $unmergedFields)) {
                    $output[$fieldName] = array_merge($output[$fieldName], $fieldDefs);
                } else {
                    $output[$fieldName] = $fieldDefs;
                }
            }

            if (isset($fieldTypeMetadata['linkDefs'])) {
                $linkDefs = $this->getMetadataHelper()->getLinkDefsInFieldMeta(
                    $entityType, $fieldParams, $fieldTypeMetadata['linkDefs']
                );
                if (isset($linkDefs)) {
                    if (!isset($entityMetadata['links'])) {
                        $entityMetadata['links'] = [];
                    }
                    $entityMetadata['links'] = Util::merge(
                        [$fieldName => $linkDefs],
                        $entityMetadata['links']
                    );
                }
            }
        }

        return $output;
    }

    /**
     * Correct fields definitions based on Espo\Custom\Core\Utils\Database\Orm\Fields.
     */
    protected function correctFields(string $entityType, array $ormMetadata) : array
    {
        $entityDefs = $this->getEntityDefs();

        $entityMetadata = $ormMetadata[$entityType];

        //load custom field definitions and customCodes
        foreach ($entityMetadata['fields'] as $fieldName => $fieldParams) {
            if (empty($fieldParams['type'])) {
                continue;
            }

            $fieldType = $fieldParams['type'];

            $className = $this->metadata->get(['fields', $fieldType, 'converterClassName']);

            if (!$className) {
                $className = 'Espo\Custom\Core\Utils\Database\Orm\Fields\\' . ucfirst($fieldType);

                if (!class_exists($className)) {
                    $className = 'Espo\Core\Utils\Database\Orm\Fields\\' . ucfirst($fieldType);
                }
            }

            if (class_exists($className) && method_exists($className, 'load')) {
                $helperClass = new $className($this->metadata, $ormMetadata, $entityDefs, $this->config);

                $fieldResult = $helperClass->process($fieldName, $entityType);

                if (isset($fieldResult['unset'])) {
                    $ormMetadata = Util::unsetInArray($ormMetadata, $fieldResult['unset']);

                    unset($fieldResult['unset']);
                }

                $ormMetadata = Util::merge($ormMetadata, $fieldResult);
            }

            $defaultAttributes = $this->metadata->get(['entityDefs', $entityType, 'fields', $fieldName, 'defaultAttributes']);

            if ($defaultAttributes && array_key_exists($fieldName, $defaultAttributes)) {
                $defaultMetadataPart = [
                    $entityType => [
                        'fields' => [
                            $fieldName => [
                                'default' => $defaultAttributes[$fieldName]
                            ]
                        ]
                    ]
                ];

                $ormMetadata = Util::merge($ormMetadata, $defaultMetadataPart);
            }
        }

        // @todo move to separate file
        $scopeDefs = $this->getMetadata()->get('scopes.'.$entityType);

        if (isset($scopeDefs['stream']) && $scopeDefs['stream']) {
            if (!isset($entityMetadata['fields']['isFollowed'])) {
                $ormMetadata[$entityType]['fields']['isFollowed'] = [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'notExportable' => true,
                ];

                $ormMetadata[$entityType]['fields']['followersIds'] = [
                    'type' => 'jsonArray',
                    'notStorable' => true,
                    'notExportable' => true,
                ];
                $ormMetadata[$entityType]['fields']['followersNames'] = [
                    'type' => 'jsonObject',
                    'notStorable' => true,
                    'notExportable' => true,
                ];
            }
        }

        return $ormMetadata;
    }

    protected function convertField(string $entityType, string $fieldName, array $fieldParams, ?array $fieldTypeMetadata = null)
    {
        if (!isset($fieldTypeMetadata)) {
            $fieldTypeMetadata = $this->getMetadataHelper()->getFieldDefsByType($fieldParams);
        }

        if (isset($fieldTypeMetadata['fieldDefs'])) {
            $fieldParams = Util::merge($fieldParams, $fieldTypeMetadata['fieldDefs']);
        }

        if ($fieldParams['type'] == 'base' && isset($fieldParams['dbType'])) {
            $fieldParams['notStorable'] = false;
        }

        if (!empty($fieldTypeMetadata['skipOrmDefs']) || !empty($fieldParams['skipOrmDefs'])) {
            return false;
        }

        if (
            isset($fieldParams['notNull']) && !$fieldParams['notNull'] && isset($fieldParams['required']) &&
            $fieldParams['required']
        ) {
            unset($fieldParams['notNull']);
        }

        $fieldDefs = $this->getInitValues($fieldParams);

        if (isset($fieldParams['db']) && $fieldParams['db'] === false) {
            $fieldDefs['notStorable'] = true;
        }

        if (
            isset($fieldDefs['type']) && !isset($fieldDefs['len']) &&
            in_array($fieldDefs['type'], array_keys($this->defaultLength))
        ) {
            $fieldDefs['len'] = $this->defaultLength[$fieldDefs['type']];
        }

        return $fieldDefs;
    }

    protected function convertLinks(string $entityType, array $entityMetadata, array $ormMetadata) : array
    {
        if (!isset($entityMetadata['links'])) {
            return [];
        }

        $relationships = [];
        foreach ($entityMetadata['links'] as $linkName => $linkParams) {

            if (isset($linkParams['skipOrmDefs']) && $linkParams['skipOrmDefs'] === true) {
                continue;
            }

            $convertedLink = $this->getRelationManager()->convert($linkName, $linkParams, $entityType, $ormMetadata);

            if (isset($convertedLink)) {
                $relationships = Util::merge($convertedLink, $relationships);
            }
        }

        return $relationships;
    }

    protected function getInitValues(array $fieldParams)
    {
        $values = [];

        foreach($this->fieldAccordances as $espoType => $ormType) {

            if (!array_key_exists($espoType, $fieldParams)) {
                continue;
            }

            switch ($espoType) {
                case 'default':
                    if (is_array($fieldParams[$espoType]) || !preg_match('/^javascript:/i', $fieldParams[$espoType])) {
                        $values[$ormType] = $fieldParams[$espoType];
                    }
                    break;

                default:
                    $values[$ormType] = $fieldParams[$espoType];
                    break;
            }
        }

        if (isset($fieldParams['type'])) {
            $values['fieldType'] = $fieldParams['type'];
        }

        return $values;
    }

    protected function applyFullTextSearch(array &$ormMetadata, string $entityType)
    {
        if (!$this->getDatabaseHelper()->isTableSupportsFulltext(Util::toUnderScore($entityType))) return;
        if (!$this->getMetadata()->get(['entityDefs', $entityType, 'collection', 'fullTextSearch'])) return;

        $fieldList = $this->getMetadata()->get(['entityDefs', $entityType, 'collection', 'textFilterFields'], ['name']);

        $fullTextSearchColumnList = [];

        foreach ($fieldList as $field) {
            $defs = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $field], []);
            if (empty($defs['type'])) continue;
            $fieldType = $defs['type'];
            if (!empty($defs['notStorable'])) continue;
            if (!$this->getMetadata()->get(['fields', $fieldType, 'fullTextSearch'])) continue;

            $partList = $this->getMetadata()->get(['fields', $fieldType, 'fullTextSearchColumnList']);
            if ($partList) {
                if ($this->getMetadata()->get(['fields', $fieldType, 'naming']) === 'prefix') {
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
            if (!array_key_exists('indexes', $ormMetadata[$entityType])) {
                $ormMetadata[$entityType]['indexes'] = [];
            }
            $ormMetadata[$entityType]['indexes']['system_fullTextSearch'] = [
                'columns' => $fullTextSearchColumnList,
                'flags' => ['fulltext']
            ];
        }
    }

    protected function applyIndexes(&$ormMetadata, $entityType)
    {
        if (isset($ormMetadata[$entityType]['fields'])) {
            $indexList = SchemaUtils::getEntityIndexListByFieldsDefs($ormMetadata[$entityType]['fields']);
            foreach ($indexList as $indexName => $indexParams) {
                if (!isset($ormMetadata[$entityType]['indexes'][$indexName])) {
                    $ormMetadata[$entityType]['indexes'][$indexName] = $indexParams;
                }
            }
        }

        if (isset($ormMetadata[$entityType]['indexes'])) {
            foreach ($ormMetadata[$entityType]['indexes'] as $indexName => &$indexData) {
                if (!isset($indexData['key'])) {
                    $indexType = SchemaUtils::getIndexTypeByIndexDefs($indexData);
                    $indexData['key'] = SchemaUtils::generateIndexName($indexName, $indexType);
                }
            }
        }

        if (isset($ormMetadata[$entityType]['relations'])) {
            foreach ($ormMetadata[$entityType]['relations'] as $relationName => &$relationData) {
                if (isset($relationData['indexes'])) {
                    foreach ($relationData['indexes'] as $indexName => &$indexData) {
                        $indexType = SchemaUtils::getIndexTypeByIndexDefs($indexData);
                        $indexData['key'] = SchemaUtils::generateIndexName($indexName, $indexType);
                    }
                }
            }
        }
    }

    protected function createAdditionalEntityTypes(string $entityType, array $defs) : array
    {
        if (empty($defs['additionalTables'])) {
            return [];
        }

        $additionalDefs = $defs['additionalTables'];

        return $additionalDefs;
    }

    protected function createRelationsEntityDefs(string $entityType, array $defs) : array
    {
        $result = [];

        foreach ($defs['relations'] as $relationParams) {
            if ($relationParams['type'] !== 'manyMany') continue;

            $relationEntityType = ucfirst($relationParams['relationName']);

            $itemDefs = [
                'skipRebuild' => true,
                'fields' => [
                    'id' => [
                        'type' => 'id',
                        'autoincrement' => true,
                        'dbType' => 'varchar',
                    ],
                    'deleted' => [
                        'type' => 'bool'
                    ],
                ],
            ];

            foreach ($relationParams['midKeys'] ?? [] as $key) {
                $itemDefs['fields'][$key] = [
                    'type' => 'foreignId',
                ];
            }

            foreach ($relationParams['additionalColumns'] ?? [] as $columnName => $columnItem) {
                $itemDefs['fields'][$columnName] = [
                    'type' => $columnItem['type'] ?? 'varchar',
                ];
            }

            $result[$relationEntityType] = $itemDefs;
        }

        return $result;
    }
}
