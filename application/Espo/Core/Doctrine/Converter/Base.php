<?php

namespace Espo\Core\Doctrine\Converter;

use Espo\Core\Utils\Util;

class Base
{
	private $entityManager;
	private $metadata;
	private $fileManager;
	private $doctrineHelper;
	private $linkConverter;

	protected $defaultFieldType = 'varchar';
	protected $defaultNaming = 'postfix';

	/*
	* //pair espo:doctrine
	*/
	protected $fieldAccordances = array(
		'type' => 'type',
		'maxLength' => 'length',
		'default' => array(
		   'condition' => '^javascript:',
		   'conditionEquals' => false,
		   'value' => array(
			   'options' => array (
                    'default' => '{0}',
                ),
		   ),
		),
	);


	/**
	* @var array $meta - metadata array
	*/
	private $meta;


    public function __construct(\Espo\Core\EntityManager $entityManager, \Espo\Core\Utils\Metadata $metadata, \Espo\Core\Utils\File\Manager $fileManager)
	{
		$this->entityManager = $entityManager;
		$this->metadata = $metadata;
		$this->fileManager = $fileManager;

		$this->doctrineHelper = new \Espo\Core\Doctrine\Helper($this->getEntityManager()->getWrapped());

		$this->linkConverter = new \Espo\Core\Doctrine\Converter\Link($this->metadata);
	}

	protected function getEntityManager()
	{
		return $this->entityManager;
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}

	protected function getFileManager()
	{
    	return $this->fileManager;
	}

	protected function getDoctrineHelper()
	{
    	return $this->doctrineHelper;
	}

	protected function getLinkConverter()
	{
    	return $this->linkConverter;
	}

	/**
	* Metadata conversion from Espo format into Doctrine
    *
	* @param object $meta
	*
	* @return bool
	*/

	//public function process() 
	public function process()
	{
 		//echo '<pre>';
		$GLOBALS['log']->add('Debug', 'Metadata:get() - converting to doctrine metadata');

        $entityDefs = $this->getMetadata()->get('entityDefs');

 		$convertedMeta = array();
        foreach($entityDefs as $entityName => $entityMeta) {

        	//echo '['.$entityName.']<br />';

			if (empty($entityMeta)) {
		    	$GLOBALS['log']->add('ERROR', 'EspoConverter:convert(), Entity:'.$entityName.' - metadata cannot be converted into Doctrine format');
				continue;
			}

			$convertedMeta[$entityName] = array(
				'type' => 'entity',
				'table' => Util::fromCamelCase($entityName, '_'), //TODO: if need to convert to underscore
				'id' => array(
					'id' => array(
				        'type' => 'string',
				        'generator' => array('strategy' => 'UUID'),
					)
				),
			);

         	$convertedMeta[$entityName]['fields'] = $this->convertFields($entityName, $entityMeta);

			$convertedLinks = $this->convertLinks($entityName, $entityMeta, $entityDefs, $convertedMeta);

            //$convertedMeta = Util::merge($convertedMeta, $convertedLinks); //for link is need to define for two entities at once
        }



        /*echo '<hr />';
		print_r($convertedMeta);
        exit;*/

		return;

		//save doctrine meta to files
        $cacheDir = $this->getMetadata()->getMetaConfig()->doctrineCache;
		$this->getFileManager()->removeFilesInDir($cacheDir); //remove all existing files

		$result= true;
		foreach ($convertedMeta as $entityName => $doctineMeta) {
			$entityFullName = $this->getMetadata()->getEntityPath($entityName);
			$doctrineMeta = array($entityFullName => $doctineMeta);

            //create a doctrine metadata file like "Espo.Entities.User.php"
			$fileName = str_replace('\\', '.', $entityFullName).'.php';
            $result &= $this->getFileManager()->setContent($this->getFileManager()->getPHPFormat($doctrineMeta), $cacheDir, $fileName);
			//END: create a doctrine metadata file
		}
		//END: save doctrine meta to files

        return $result;
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
		$outputMeta = array();
		foreach($entityMeta['fields'] as $fieldName => $fieldParams) {

        	$fieldName = Util::fromCamelCase($fieldName, '_');

			//check if "fields" option exists in $fieldMeta
			$fieldTypeMeta = $this->getMetadata()->get('fields.'.$fieldParams['type']);
			if (isset($fieldTypeMeta['fields']) && is_array($fieldTypeMeta['fields'])) {

				$namingType = isset($fieldTypeMeta['naming']) ? $fieldTypeMeta['naming'] : $this->defaultNaming;
            	foreach($fieldTypeMeta['fields'] as $subFieldName => $subFieldParams) {

					$subFieldNameNaming = Util::fromCamelCase( Util::getNaming($fieldName, $subFieldName, $namingType, '_'), '_' );
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

        return $outputMeta;
	}



	/*It can be moved to separate file*/
	protected function convertField($entityName, $fieldName, array $fieldParams)
	{
		//set default type if exists
       	if (!isset($fieldParams['type']) || empty($fieldParams['type'])) {
       		$GLOBALS['log']->add('WARNING', 'Field type does not exist for '.$entityName.':'.$fieldName.'. Use default type ['.$this->defaultFieldType.']');
			$fieldParams['type'] = $this->defaultFieldType;
       	} //END: set default type if exists

		$fieldTypeMeta = $this->getMetadata()->get('fields.'.$fieldParams['type']);

		//check if field need to be saved in database
       	if ( (isset($fieldParams['db']) && $fieldParams['db'] === false) || (isset($fieldTypeMeta['database']['db']) && !$fieldTypeMeta['database']['db'] === false) ) {
       		return false;
       	} //END: check if field need to be saved in database

		$fieldDefs = $this->getInitValues($fieldParams);

		//merge database options from field definition
		if (isset($fieldTypeMeta['database'])) {
        	$fieldDefs = Util::merge($fieldDefs, $fieldTypeMeta['database']);
		}

		return $fieldDefs;
	}



	protected function convertLinks($entityName, $entityMeta, array $entityDefs, array $convertedMeta)
	{
    	if (!isset($entityMeta['links'])) {
			return array();
    	}

		foreach($entityMeta['links'] as $linkName => $linkParams) {
			//echo $linkName.'<br />';
			//print_r($linkParams);

			$linkEntityName = $this->getLinkConverter()->getLinkEntityName($entityName, $linkParams);
			//print_r($entityDefs[$linkEntityName]['links']);
			//print_r($convertedMeta[$linkEntityName]);

			$currentType = $linkParams['type'];
			$parentType = '';

			$foreignLink = $this->getForeignLink($linkName, $linkParams, $entityDefs[$linkEntityName]);

            $method = $currentType;
			if ($foreignLink !== false) {
            	$method .= '-'.$foreignLink['params']['type'];
			}
            $method = Util::toCamelCase($method);


			/*if ($method == 'belongsToParent') {
            	die($entityName.' - '.$linkName);
			}*/


			if (method_exists($this->getLinkConverter(), $method)) {
            	$convertedLink = $this->getLinkConverter()->process($method, $entityName, array('name'=>$linkName, 'params'=>$linkParams), $foreignLink);
                //print_r($convertedLink);
			}

			//echo $method.' = '.$currentType.' - '.$foreignLink['type'].'<br />';


		}

		return $convertedMeta;
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


?>