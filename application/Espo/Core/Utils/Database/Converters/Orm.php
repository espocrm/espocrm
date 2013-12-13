<?php

namespace Espo\Core\Utils\Database\Converters;

use Espo\Core\Utils\Util,
	Espo\ORM\Entity;

class Orm
{
	private $metadata;

	private $links;

	protected $defaultFieldType = 'varchar';
	protected $defaultNaming = 'postfix';

	protected $defaultLength = array(
		'varchar' => 255,
		'int' => 11,
	);

	/*
	* //pair espo:doctrine
	*/
	protected $fieldAccordances = array(
		'type' => 'type',
		'maxLength' => 'len',
		'default' => array(
		   'condition' => '^javascript:',
		   'conditionEquals' => false,
		   'value' => array(
           		'default' => '{0}',
		   ),
		),
	);


	public function __construct(\Espo\Core\Utils\Metadata $metadata)
	{
    	$this->metadata = $metadata;

		$this->links = new \Espo\Core\Utils\Database\Links($this->metadata);
	}


	protected function getMetadata()
	{
		return $this->metadata;
	}

	protected function getLinks()
	{
		return $this->links;
	}



	//convertToDatabaseFormat
	public function process($entityName, $entityMeta, $entityDefs)
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

        return Util::merge($ormMeta, $convertedLinks);
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
			),
		);

		foreach($entityMeta['fields'] as $fieldName => $fieldParams) {

        	//$fieldName = Util::fromCamelCase($fieldName, '_');

			//check if "fields" option exists in $fieldMeta
            $fieldParams['type'] = isset($fieldParams['type']) ? $fieldParams['type'] : '';

			$fieldTypeMeta = $this->getMetadata()->get('fields.'.$fieldParams['type']);
			if (isset($fieldTypeMeta['fields']) && is_array($fieldTypeMeta['fields'])) {

				$namingType = isset($fieldTypeMeta['naming']) ? $fieldTypeMeta['naming'] : $this->defaultNaming;
            	foreach($fieldTypeMeta['fields'] as $subFieldName => $subFieldParams) {

					//$subFieldNameNaming = Util::fromCamelCase( Util::getNaming($fieldName, $subFieldName, $namingType, '_'), '_' );
					$subFieldNameNaming = Util::getNaming($fieldName, $subFieldName, $namingType);
            		if (!isset($entityMeta['fields'][$subFieldNameNaming])) {
						$subFieldDefs = $this->convertField($entityName, $subFieldName, $subFieldParams);
						if ($subFieldDefs !== false) {
							$outputMeta[$subFieldNameNaming] = $subFieldDefs; //push fieldDefs to the main array
						}
            		}

            	}

			} else {
            	$fieldDefs = $this->convertField($entityName, $fieldName, $fieldParams);
				if ($fieldDefs !== false) {
					$outputMeta[$fieldName] = $fieldDefs; //push fieldDefs to the main array
				}
			}

			/*Make actions for different types like "link", "linkMultiple", "linkParent" */
		}


		if (!isset($outputMeta['deleted'])) {
        	$outputMeta['deleted'] = array(
				'type' => Entity::BOOL,
				'default' => 0,
			);
		}

        return $outputMeta;
	}



	protected function convertField($entityName, $fieldName, array $fieldParams)
	{
		//set default type if exists
       	if (!isset($fieldParams['type']) || empty($fieldParams['type'])) {
       		$GLOBALS['log']->add('WARNING', 'Field type does not exist for '.$entityName.':'.$fieldName.'. Use default type ['.$this->defaultFieldType.']');
			$fieldParams['type'] = $this->defaultFieldType;
       	} //END: set default type if exists

		$fieldTypeMeta = $this->getMetadata()->get('fields.'.$fieldParams['type']);

		//check if need to skip this field into database metadata
		if (isset($fieldTypeMeta['database']['skip']) && $fieldTypeMeta['database']['skip'] === true) {
        	return false;
		}

		$fieldDefs = $this->getInitValues($fieldParams);

		//check if field need to be saved in database
		//TODO change entytyDefs from db:false to notStorable:true
       	if ( (isset($fieldParams['db']) && $fieldParams['db'] === false) || (isset($fieldTypeMeta['database']['notStorable']) && !$fieldTypeMeta['database']['notStorable'] === true) ) {
       		$fieldDefs['notStorable'] = true;
       	} //END: check if field need to be saved in database

		//merge database options from field definition
		if (isset($fieldTypeMeta['database'])) {
        	$fieldDefs = Util::merge($fieldDefs, $fieldTypeMeta['database']);
		}

		//check and set a field length
		if (!isset($fieldDefs['len']) && in_array($fieldDefs['type'], array_keys($this->defaultLength))) {
        	$fieldDefs['len'] = $this->defaultLength[$fieldDefs['type']];
		} //END: check and set a field length

		return $fieldDefs;
	}



	protected function convertLinks($entityName, $entityMeta, array $entityDefs)
	{
    	if (!isset($entityMeta['links'])) {
			return array();
    	}

		$relationships = array();
		foreach($entityMeta['links'] as $linkName => $linkParams) {
			//echo $linkName.'<br />';
			//print_r($linkParams);

			$linkEntityName = $this->getLinks()->getLinkEntityName($entityName, $linkParams);
			//print_r($entityDefs[$linkEntityName]['links']);
			//print_r($convertedMeta[$linkEntityName]);

			$currentType = $linkParams['type'];
			$parentType = '';

			$foreignLink = $this->getForeignLink($linkName, $linkParams, $entityDefs[$linkEntityName]);

			$method = $currentType;
			unset($reverseMethod);
			if ($foreignLink !== false) {
            	$method .= '-'.$foreignLink['params']['type'];
                $reverseMethod = Util::toCamelCase( $foreignLink['params']['type'].'-'.$currentType );
			}
            $method = Util::toCamelCase($method);


			//echo
			/*if ($method == 'hasManyBelongsTo' || $reverseMethod == 'belongshasManyBelongsToTo') {
            	die($entityName.' - '.$linkName);
			}*/


			if (method_exists($this->getLinks(), $method)) {  //ex. hasManyHasMany
            	$convertedLink = $this->getLinks()->process($method, $entityName, array('name'=>$linkName, 'params'=>$linkParams), $foreignLink);
			} else if (method_exists($this->getLinks(), $currentType)) { //ex. hasMany
            	$convertedLink = $this->getLinks()->process($currentType, $entityName, array('name'=>$linkName, 'params'=>$linkParams), $foreignLink);
			}

			/*else if (isset($reverseMethod) && method_exists($this->getLinks(), $reverseMethod)) {
            	$convertedLink = $this->getLinks()->process($reverseMethod, $entityName, $foreignLink, array('name'=>$linkName, 'params'=>$linkParams));
			}   */

			//$relationships = Util::merge($relationships, $convertedLink);
			$relationships = Util::merge($convertedLink, $relationships);

			//echo $method.' = '.$currentType.' - '.$foreignLink['type'].'<br />';
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

        $parentLinkName = strtolower($parentLinkName);

		foreach($currentEntityDefs['links'] as $linkName => $linkParams) {
        	if (isset($linkParams['foreign']) && strtolower($linkParams['foreign']) == $parentLinkName) {
				return array(
					'name' => $linkName,
					'params' => $linkParams,
				);
        	}
		}

		return false;
	}



	protected function getInitValues(array $fieldParams)
	{
		$values = array();
		foreach($this->fieldAccordances as $espoType => $doctrineType) {

        	if (isset($fieldParams[$espoType]) && !empty($fieldParams[$espoType])) {

				if (is_array($doctrineType))  {

                    $conditionRes = false;
					if (!is_array($fieldParams[$espoType])) {
                    	$conditionRes = preg_match('/'.$doctrineType['condition'].'/i', $fieldParams[$espoType]);
					}

					if (!$conditionRes || ($conditionRes && $conditionRes === $doctrineType['conditionEquals']) )  {
						$value = is_array($fieldParams[$espoType]) ? json_encode($fieldParams[$espoType]) : $fieldParams[$espoType];
						$values = Util::merge( $values, Util::replaceInArray('{0}', $value, $doctrineType['value']) );
					}
				} else {
                	$values[$doctrineType] = $fieldParams[$espoType];
				}

			}
		}

		return $values;
	}
}