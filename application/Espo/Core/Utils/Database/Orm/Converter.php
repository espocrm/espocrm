<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

namespace Espo\Core\Utils\Database\Orm;

use Espo\Core\Utils\Util,
	Espo\ORM\Entity;

class Converter
{
	private $metadata;
	private $fileManager;

	private $relationManager;

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
		'autoincrement' => 'autoincrement',
		'notStorable' => 'notStorable',
		'link' => 'relation',
		'field' => 'foreign',  //todo change "foreign" to "field"
		'unique' => 'unique',
		'index' => 'index',
		/*'conditions' => 'conditions',
		'additionalColumns' => 'additionalColumns',	*/
		'default' => array(
		   'condition' => '^javascript:',
		   'conditionEquals' => false,
		   'value' => array(
				'default' => '{0}',
		   ),
		),
	);

	protected $idParams = array(
		'dbType' => 'varchar',
		'len' => '24',
	);


	public function __construct(\Espo\Core\Utils\Metadata $metadata, \Espo\Core\Utils\File\Manager $fileManager)
	{
		$this->metadata = $metadata;
		$this->fileManager = $fileManager; //need to featue with ormHooks. Ex. isFollowed field

		$this->relationManager = new RelationManager($this->metadata);
	}


	protected function getMetadata()
	{
		return $this->metadata;
	}

	protected function getFileManager()
	{
		return $this->fileManager;
	}

	protected function getRelationManager()
	{
		return $this->relationManager;
	}


	public function process()
	{
		$entityDefs = $this->getMetadata()->get('entityDefs');

		$ormMeta = array();
		foreach($entityDefs as $entityName => $entityMeta) {

			if (empty($entityMeta)) {
				$GLOBALS['log']->critical('Orm\Converter:process(), Entity:'.$entityName.' - metadata cannot be converted into ORM format');
				continue;
			}

			$ormMeta = Util::merge($ormMeta, $this->convertEntity($entityName, $entityMeta, $entityDefs));
		}

		$ormMeta = $this->afterProcess($ormMeta);

		return $ormMeta;
	}



	//convertToDatabaseFormat
	protected function convertEntity($entityName, $entityMeta, $entityDefs)
	{
		$ormMeta = array();
		$ormMeta[$entityName] = array(
			'fields' => array(
			),
			'relations' => array(
			),
		);

		$ormMeta[$entityName]['fields'] = $this->convertFields($entityName, $entityMeta);

		$convertedLinks = $this->convertLinks($entityName, $entityMeta, $entityDefs);

		$ormMeta = Util::merge($ormMeta, $convertedLinks);

		return $ormMeta;
	}


	public function afterProcess(array $meta)
	{
		//load custom field definitions and customCodes
		foreach($meta as $entityName => &$entityParams) {
			foreach($entityParams['fields'] as $fieldName => $fieldParams) {

				//load custom field definitions
				$fieldType = ucfirst($fieldParams['type']);
				$className = '\Espo\Custom\Core\Utils\Database\Orm\Fields\\'.$fieldType;
				if (!class_exists($className)) {
					$className = '\Espo\Core\Utils\Database\Orm\Fields\\'.$fieldType;
				}

				if (class_exists($className) && method_exists($className, 'load')) {
					$helperClass = new $className($this->metadata);
					$fieldResult = $helperClass->load(
						array('name' => $entityName, 'params' => $entityParams),
						array('name' => $fieldName, 'params' => $fieldParams)
					);
					if (isset($fieldResult['unset'])) {
						$meta = Util::unsetInArray($meta, $fieldResult['unset']);
						unset($fieldResult['unset']);
					}

					$meta = Util::merge($meta, $fieldResult);
				} //END: load custom field definitions


				//todo move to separate file
				//add a field 'isFollowed' for scopes with 'stream => true'
				$scopeDefs = $this->getMetadata()->get('scopes.'.$entityName);
				if (isset($scopeDefs['stream']) && $scopeDefs['stream']) {
					if (!isset($entityParams['fields']['isFollowed'])) {
						$entityParams['fields']['isFollowed'] = array(
							'type' => 'varchar',
							'notStorable' => true,
						);
					}
				} //END: add a field 'isFollowed' for stream => true

			}
		}

		foreach($meta as $entityName => &$entityParams) {
			foreach($entityParams['fields'] as $fieldName => &$fieldParams) {

				switch ($fieldParams['type']) {
					case 'id':
						if ($fieldParams['dbType'] != 'int') {
							$fieldParams = array_merge($fieldParams, $this->idParams);
						}
						break;

					case 'foreignId':
						$fieldParams = array_merge($fieldParams, $this->idParams);
						$fieldParams['notNull'] = false;
						break;

					case 'foreignType':
						$fieldParams['dbType'] = Entity::VARCHAR;
						$fieldParams['len'] = $this->defaultLength['varchar'];
						break;

					case 'bool':
						$fieldParams['default'] = isset($fieldParams['default']) ? (bool) $fieldParams['default'] : $this->defaultValue['bool'];
						break;
				}
			}

		}

		return $meta;
	}


	/**
	 * Metadata conversion from Espo format into Doctrine
	 *
	 * @param string $entityName
	 * @param array $entityMeta
	 *
	 * @return array
	 */
	protected function convertFields($entityName, $entityMeta)
	{
		$outputMeta = array(
			'id' => array(
				'type' => Entity::ID,
				'dbType' => 'varchar',
			),
			'name' => array(
				'type' => isset($entityMeta['fields']['name']['type']) ? $entityMeta['fields']['name']['type'] : Entity::VARCHAR,
				'notStorable' => true,
			),
		);

		foreach($entityMeta['fields'] as $fieldName => $fieldParams) {

			//check if "fields" option exists in $fieldMeta
			$fieldParams['type'] = isset($fieldParams['type']) ? $fieldParams['type'] : '';
			$fieldTypeMeta = $this->getMetadata()->get('fields.'.$fieldParams['type']);

			if (isset($fieldTypeMeta['fields']) && is_array($fieldTypeMeta['fields'])) {

				foreach($fieldTypeMeta['actualFields'] as $subFieldName) {

					$subField = $this->convertActualFields($entityName, $fieldName, $fieldParams, $subFieldName, $fieldTypeMeta);

					if (!isset($outputMeta[ $subField['naming'] ])) {
						$subFieldDefs = $this->convertField($entityName, $subField['name'], $subField['params']);
						if ($subFieldDefs !== false) {
							$outputMeta[ $subField['naming'] ] = $subFieldDefs; //push fieldDefs to the main array
						}
					}
				}

			} else {
				$fieldDefs = $this->convertField($entityName, $fieldName, $fieldParams);
				if ($fieldDefs !== false) {
					$outputMeta[$fieldName] = $fieldDefs; //push fieldDefs to the main array
				}
			}
		}

		if (!isset($outputMeta['deleted'])) {
			$outputMeta['deleted'] = array(
				'type' => Entity::BOOL,
				'default' => false,
			);
		}

		return $outputMeta;
	}



	protected function convertField($entityName, $fieldName, array $fieldParams)
	{
		//set default type if exists
		if (!isset($fieldParams['type']) || empty($fieldParams['type'])) {
			$GLOBALS['log']->warning('Field type does not exist for '.$entityName.':'.$fieldName.'. Use default type ['.$this->defaultFieldType.']');
			$fieldParams['type'] = $this->defaultFieldType;
		} //END: set default type if exists

		if (isset($fieldParams['dbType'])) {
			$fieldTypeMeta = $this->getMetadata()->get('fields.'.$fieldParams['dbType']);
		} else {
			$fieldTypeMeta = $this->getMetadata()->get('fields.'.$fieldParams['type']);
		}

		//check if need to skip this field into database metadata
		if (isset($fieldTypeMeta['database']['skip']) && $fieldTypeMeta['database']['skip'] === true) {
			return false;
		}

		//merge database options from field definition
		if (isset($fieldTypeMeta['database'])) {
			$fieldParams = Util::merge($fieldParams, $fieldTypeMeta['database']);
		}

		//if defined 'notNull => false' and 'required => true', then remove 'notNull'
		if (isset($fieldParams['notNull']) && !$fieldParams['notNull'] && isset($fieldParams['required']) && $fieldParams['required']) {
			unset($fieldParams['notNull']);
		} //END


		$fieldDefs = $this->getInitValues($fieldParams);

		//check if field need to be saved in database
		//TODO change entytyDefs from db:false to notStorable:true
		if ( (isset($fieldParams['db']) && $fieldParams['db'] === false) || (isset($fieldTypeMeta['database']['notStorable']) && !$fieldTypeMeta['database']['notStorable'] === true) ) {
			$fieldDefs['notStorable'] = true;
		} //END: check if field need to be saved in database

		//merge database options from field definition
		/*if (isset($fieldTypeMeta['database'])) {
			$fieldDefs = Util::merge($fieldDefs, $fieldTypeMeta['database']);
		}*/

		//check and set a field length
		if (!isset($fieldDefs['len']) && in_array($fieldDefs['type'], array_keys($this->defaultLength))) {
			$fieldDefs['len'] = $this->defaultLength[$fieldDefs['type']];
		} //END: check and set a field length

		return $fieldDefs;
	}

	protected function convertActualFields($entityName, $fieldName, $fieldParams, $subFieldName, $fieldTypeMeta)
	{
		$subField = array();

		$subField['params'] = $this->getInitValues($fieldParams);

		if (isset($fieldTypeMeta['database'])) {
			$subField['params'] = Util::merge($subField['params'], $fieldTypeMeta['database']);
		}

		//if empty field name, then use the main field
		if (trim($subFieldName) == '') {

			if (!isset($fieldTypeMeta['database'])) {
				$GLOBALS['log']->critical('Empty field defs for ['.$entityName.':'.$fieldName.'] using "actualFields". Main field ['.$fieldName.']');
			}

			$subField['name'] = $fieldName;
			$subField['naming'] = $fieldName;

		} else {

			if (!isset($fieldTypeMeta['fields'][$subFieldName])) {
				$GLOBALS['log']->critical('Empty field defs for ['.$entityName.':'.$subFieldName.'] using "actualFields". Main field ['.$fieldName.']');
			}

			$namingType = isset($fieldTypeMeta['naming']) ? $fieldTypeMeta['naming'] : $this->defaultNaming;

			$subField['name'] = $subFieldName;
			$subField['naming'] = Util::getNaming($fieldName, $subFieldName, $namingType);
			if (isset($fieldTypeMeta['fields'][$subFieldName])) {
				$subField['params'] = Util::merge($subField['params'], $fieldTypeMeta['fields'][$subFieldName]);
			}

		}

		return $subField;
	}



	protected function convertLinks($entityName, $entityMeta, array $entityDefs)
	{
		if (!isset($entityMeta['links'])) {
			return array();
		}

		$relationships = array();
		foreach($entityMeta['links'] as $linkName => $linkParams) {

			$linkEntityName = $this->getRelationManager()->getLinkEntityName($entityName, $linkParams);

			$currentType = $linkParams['type'];
			$parentType = '';

			$foreignLink = $this->getForeignLink($linkName, $linkParams, $entityDefs[$linkEntityName]);

			$method = $currentType;
			if ($foreignLink !== false) {
				$method .= '-'.$foreignLink['params']['type'];
			}
			$method = Util::toCamelCase($method);

			if ( $this->getRelationManager()->isRelationExists($method) ) {  //ex. hasManyHasMany
				$convertedLink = $this->getRelationManager()->process($method, $entityName, array('name'=>$linkName, 'params'=>$linkParams), $foreignLink);
			} else { //ex. hasMany
				$convertedLink = $this->getRelationManager()->process($currentType, $entityName, array('name'=>$linkName, 'params'=>$linkParams), $foreignLink);
			}

			if ($convertedLink !== false) {
				$relationships = Util::merge($convertedLink, $relationships);
			}
		}

		return $relationships;
	}


	/**
	* Get foreign Link
	*
	* @param string $parentLinkName
	* @param array $parentLinkParams
	* @param array $currentEntityDefs
	*
	* @return array - in format array('name', 'params')
	*/
	protected function getForeignLink($parentLinkName, $parentLinkParams, $currentEntityDefs)
	{
		if (isset($parentLinkParams['foreign']) && isset($currentEntityDefs['links'][$parentLinkParams['foreign']])) {
			return array(
				'name' => $parentLinkParams['foreign'],
				'params' => $currentEntityDefs['links'][$parentLinkParams['foreign']],
			);
		}

		return false;
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

		return $values;
	}

}