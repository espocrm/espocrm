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

namespace Espo\Core\Utils\Database\Orm;

use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\Core\Utils\Database\Schema\Utils as SchemaUtils;

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

    protected $defaultLength = array(
        'varchar' => 255,
        'int' => 11,
    );

    protected $defaultValue = array(
        'bool' => false,
    );

    /*
    * //pair Espo => ORM
    */
    protected $fieldAccordances = array(
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
        'field' => 'foreign',  //todo change "foreign" to "field"
        'unique' => 'unique',
        'index' => 'index',
        /*'conditions' => 'conditions',
        'additionalColumns' => 'additionalColumns',    */
        'default' => array(
           'condition' => '^javascript:',
           'conditionEquals' => false,
           'value' => array(
                'default' => '{0}',
           ),
        ),
        'select' => 'select',
        'orderBy' => 'orderBy',
        'where' => 'where',
        'storeArrayValues' => 'storeArrayValues',
        'binary' => 'binary',
    );

    protected $idParams = array(
        'dbType' => 'varchar',
        'len' => 24,
    );

    /**
     * Permitted Entity options which will be moved to ormMetadata
     *
     * @var array
     */
    protected $permittedEntityOptions = array(
        'indexes',
        'additionalTables',
    );

    public function __construct(\Espo\Core\Utils\Metadata $metadata, \Espo\Core\Utils\File\Manager $fileManager, \Espo\Core\Utils\Config $config = null)
    {
        $this->metadata = $metadata;
        $this->fileManager = $fileManager; //need to featue with ormHooks. Ex. isFollowed field
        $this->config = $config;

        $this->relationManager = new RelationManager($this->metadata);

        $this->metadataHelper = new \Espo\Core\Utils\Metadata\Helper($this->metadata);
        $this->databaseHelper = new \Espo\Core\Utils\Database\Helper($this->config);
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
     * Orm metadata convertation process
     *
     * @return array
     */
    public function process()
    {
        $entityDefs = $this->getEntityDefs(true);

        $ormMetadata = array();
        foreach($entityDefs as $entityName => $entityMetadata) {

            if (empty($entityMetadata)) {
                $GLOBALS['log']->critical('Orm\Converter:process(), Entity:'.$entityName.' - metadata cannot be converted into ORM format');
                continue;
            }

            $ormMetadata = Util::merge($ormMetadata, $this->convertEntity($entityName, $entityMetadata));
        }

        $ormMetadata = $this->afterProcess($ormMetadata);

        return $ormMetadata;
    }

    protected function convertEntity($entityName, $entityMetadata)
    {
        $ormMetadata = array();
        $ormMetadata[$entityName] = array(
            'fields' => [],
            'relations' => []
        );

        foreach ($this->permittedEntityOptions as $optionName) {
            if (isset($entityMetadata[$optionName])) {
                $ormMetadata[$entityName][$optionName] = $entityMetadata[$optionName];
            }
        }

        $ormMetadata[$entityName]['fields'] = $this->convertFields($entityName, $entityMetadata);
        $ormMetadata = $this->correctFields($entityName, $ormMetadata);

        $convertedLinks = $this->convertLinks($entityName, $entityMetadata, $ormMetadata);

        $ormMetadata = Util::merge($ormMetadata, $convertedLinks);

        $this->applyFullTextSearch($ormMetadata, $entityName);
        $this->applyIndexes($ormMetadata, $entityName);

        if (!empty($entityMetadata['collection']) && is_array($entityMetadata['collection'])) {
            $collectionDefs = $entityMetadata['collection'];
            $ormMetadata[$entityName]['collection'] = array();

            if (array_key_exists('orderByColumn', $collectionDefs)) {
                $ormMetadata[$entityName]['collection']['orderBy'] = $collectionDefs['orderByColumn'];
            } else if (array_key_exists('orderBy', $collectionDefs)) {
                if (array_key_exists($collectionDefs['orderBy'], $ormMetadata[$entityName]['fields'])) {
                    $ormMetadata[$entityName]['collection']['orderBy'] = $collectionDefs['orderBy'];
                }
            }
            $ormMetadata[$entityName]['collection']['order'] = 'ASC';
            if (array_key_exists('order', $collectionDefs)) {
                $ormMetadata[$entityName]['collection']['order'] = strtoupper($collectionDefs['order']);
            }
        }

        return $ormMetadata;
    }

    public function afterProcess(array $ormMetadata)
    {
        foreach ($ormMetadata as $entityName => &$entityParams) {
            foreach ($entityParams['fields'] as $fieldName => &$fieldParams) {

                /* remove fields without type */
                if (!isset($fieldParams['type']) && (!isset($fieldParams['notStorable']) || $fieldParams['notStorable'] === false)) {
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
                        $fieldParams['default'] = isset($fieldParams['default']) ? (bool) $fieldParams['default'] : $this->defaultValue['bool'];
                        break;

                    //todo: remove the types from ORM Metadata. Types are deprecated.
                    case 'email':
                    case 'phone':
                        break;

                    default:
                        $constName = strtoupper(Util::toUnderScore($fieldParams['type']));
                        if (!defined('\\Espo\\ORM\\Entity::' . $constName)) {
                            $fieldParams['type'] = $this->defaultFieldType;
                        }
                        break;
                }
            }
        }

        return $ormMetadata;
    }

    /**
     * Metadata conversion from Espo format into Doctrine
     *
     * @param string $entityName
     * @param array $entityMetadata
     *
     * @return array
     */
    protected function convertFields($entityName, &$entityMetadata)
    {
        //List of unmerged fields with default field definitions in $outputMeta
        $unmergedFields = array(
            'name',
        );

        $outputMeta = array(
            'id' => array(
                'type' => Entity::ID,
                'dbType' => 'varchar'
            ),
            'name' => array(
                'type' => isset($entityMetadata['fields']['name']['type']) ? $entityMetadata['fields']['name']['type'] : Entity::VARCHAR,
                'notStorable' => true
            ),
            'deleted' => array(
                'type' => Entity::BOOL,
                'default' => false
            )
        );

        foreach ($entityMetadata['fields'] as $fieldName => $fieldParams) {
            if (empty($fieldParams['type'])) continue;

            /** check if "fields" option exists in $fieldMeta */
            $fieldTypeMetadata = $this->getMetadataHelper()->getFieldDefsByType($fieldParams);

            $fieldDefs = $this->convertField($entityName, $fieldName, $fieldParams, $fieldTypeMetadata);

            if ($fieldDefs !== false) {
                //push fieldDefs to the ORM metadata array
                if (isset($outputMeta[$fieldName]) && !in_array($fieldName, $unmergedFields)) {
                    $outputMeta[$fieldName] = array_merge($outputMeta[$fieldName], $fieldDefs);
                } else {
                    $outputMeta[$fieldName] = $fieldDefs;
                }
            }

            /** check and set the linkDefs from 'fields' metadata */
            if (isset($fieldTypeMetadata['linkDefs'])) {
                $linkDefs = $this->getMetadataHelper()->getLinkDefsInFieldMeta($entityName, $fieldParams, $fieldTypeMetadata['linkDefs']);
                if (isset($linkDefs)) {
                    if (!isset($entityMetadata['links'])) {
                        $entityMetadata['links'] = array();
                    }
                    $entityMetadata['links'] = Util::merge( array($fieldName => $linkDefs), $entityMetadata['links'] );
                }
            }
        }

        return $outputMeta;
    }

    /**
     * Correct fields definitions based on \Espo\Custom\Core\Utils\Database\Orm\Fields
     *
     * @param  array  $ormMetadata
     *
     * @return array
     */
    protected function correctFields($entityName, array $ormMetadata)
    {
        $entityDefs = $this->getEntityDefs();

        $entityMetadata = $ormMetadata[$entityName];
        //load custom field definitions and customCodes
        foreach ($entityMetadata['fields'] as $fieldName => $fieldParams) {
            if (empty($fieldParams['type'])) continue;

            $fieldType = ucfirst($fieldParams['type']);
            $className = '\Espo\Custom\Core\Utils\Database\Orm\Fields\\' . $fieldType;
            if (!class_exists($className)) {
                $className = '\Espo\Core\Utils\Database\Orm\Fields\\' . $fieldType;
            }

            if (class_exists($className) && method_exists($className, 'load')) {
                $helperClass = new $className($this->metadata, $ormMetadata, $entityDefs);
                $fieldResult = $helperClass->process($fieldName, $entityName);
                if (isset($fieldResult['unset'])) {
                    $ormMetadata = Util::unsetInArray($ormMetadata, $fieldResult['unset']);
                    unset($fieldResult['unset']);
                }

                $ormMetadata = Util::merge($ormMetadata, $fieldResult);
            }

            $defaultAttributes = $this->metadata->get(['entityDefs', $entityName, 'fields', $fieldName, 'defaultAttributes']);
            if ($defaultAttributes && array_key_exists($fieldName, $defaultAttributes)) {
                $defaultMetadataPart = array(
                    $entityName => array(
                        'fields' => array(
                            $fieldName => array(
                                'default' => $defaultAttributes[$fieldName]
                            )
                        )
                    )
                );
                $ormMetadata = Util::merge($ormMetadata, $defaultMetadataPart);
            }
        }

        //todo move to separate file
        //add a field 'isFollowed' for scopes with 'stream => true'
        $scopeDefs = $this->getMetadata()->get('scopes.'.$entityName);
        if (isset($scopeDefs['stream']) && $scopeDefs['stream']) {
            if (!isset($entityMetadata['fields']['isFollowed'])) {
                $ormMetadata[$entityName]['fields']['isFollowed'] = array(
                    'type' => 'varchar',
                    'notStorable' => true,
                    'notExportable' => true,
                );

                $ormMetadata[$entityName]['fields']['followersIds'] = array(
                    'type' => 'jsonArray',
                    'notStorable' => true,
                    'notExportable' => true,
                );
                $ormMetadata[$entityName]['fields']['followersNames'] = array(
                    'type' => 'jsonObject',
                    'notStorable' => true,
                    'notExportable' => true,
                );
            }
        } //END: add a field 'isFollowed' for stream => true

        return $ormMetadata;
    }

    protected function convertField($entityName, $fieldName, array $fieldParams, $fieldTypeMetadata = null)
    {
        /** merge fieldDefs option from field definition */
        if (!isset($fieldTypeMetadata)) {
            $fieldTypeMetadata = $this->getMetadataHelper()->getFieldDefsByType($fieldParams);
        }

        if (isset($fieldTypeMetadata['fieldDefs'])) {
            $fieldParams = Util::merge($fieldParams, $fieldTypeMetadata['fieldDefs']);
        }

        if ($fieldParams['type'] == 'base' && isset($fieldParams['dbType'])) {
            $fieldParams['notStorable'] = false;
        }

        /** check if need to skipOrmDefs this field in ORM metadata */
        if (!empty($fieldTypeMetadata['skipOrmDefs']) || !empty($fieldParams['skipOrmDefs'])) {
            return false;
        }

        /** if defined 'notNull => false' and 'required => true', then remove 'notNull' */
        if (isset($fieldParams['notNull']) && !$fieldParams['notNull'] && isset($fieldParams['required']) && $fieldParams['required']) {
            unset($fieldParams['notNull']);
        }

        $fieldDefs = $this->getInitValues($fieldParams);

        /** check if the field need to be saved in database    */
        if ( (isset($fieldParams['db']) && $fieldParams['db'] === false) ) {
            $fieldDefs['notStorable'] = true;
        }

        /** check and set the field length */
        if (isset($fieldDefs['type']) && !isset($fieldDefs['len']) && in_array($fieldDefs['type'], array_keys($this->defaultLength))) {
            $fieldDefs['len'] = $this->defaultLength[$fieldDefs['type']];
        }

        return $fieldDefs;
    }

    protected function convertLinks($entityName, $entityMetadata, $ormMetadata)
    {
        if (!isset($entityMetadata['links'])) {
            return array();
        }

        $relationships = array();
        foreach ($entityMetadata['links'] as $linkName => $linkParams) {

            if (isset($linkParams['skipOrmDefs']) && $linkParams['skipOrmDefs'] === true) {
                continue;
            }

            $convertedLink = $this->getRelationManager()->convert($linkName, $linkParams, $entityName, $ormMetadata);

            if (isset($convertedLink)) {
                $relationships = Util::merge($convertedLink, $relationships);
            }
        }

        return $relationships;
    }

    protected function getInitValues(array $fieldParams)
    {
        $values = array();
        foreach($this->fieldAccordances as $espoType => $ormType) {

            if (isset($fieldParams[$espoType])) {

                if (is_array($ormType))  {

                    $conditionRes = false;
                    if (!is_array($fieldParams[$espoType])) {
                        $conditionRes = preg_match('/'.$ormType['condition'].'/i', $fieldParams[$espoType]);
                    }

                    if (!$conditionRes || ($conditionRes && $conditionRes === $ormType['conditionEquals']) )  {
                        $value = is_array($fieldParams[$espoType]) ? json_encode($fieldParams[$espoType]) : $fieldParams[$espoType];
                        $values = Util::merge( $values, Util::replaceInArray('{0}', $value, $ormType['value']) );
                    }
                } else {
                    $values[$ormType] = $fieldParams[$espoType];
                }

            }
        }

        if (isset($fieldParams['type'])) {
            $values['fieldType'] = $fieldParams['type'];
        }

        return $values;
    }

    protected function applyFullTextSearch(&$ormMetadata, $entityType)
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
        if (!isset($ormMetadata[$entityType]['indexes'])) {
            return;
        }

        foreach ($ormMetadata[$entityType]['indexes'] as $indexName => &$indexData) {
            if (!isset($indexData['key'])) {
                $indexType = SchemaUtils::getIndexTypeByIndexDefs($indexData);
                $indexData['key'] = SchemaUtils::generateIndexName($indexName, $indexType);
            }
        }
    }
}
