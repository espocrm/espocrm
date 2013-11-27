<?php

namespace Espo\Core\Doctrine;

use Espo\Core\Utils\Util;

class EspoConverter
{
	private $entityManager;
	private $metadata;
	private $fileManager;
	private $schemaTool;
	private $disconnectedClassMetadataFactory;
	private $entityGenerator;

	protected $defaultFieldType = 'varchar';

	/**
	* @var array $meta - metadata array
	*/
	private $meta;


    public function __construct(\Espo\Core\EntityManager $entityManager, \Espo\Core\Utils\Metadata $metadata, \Espo\Core\Utils\File\Manager $fileManager)
	{
		$this->entityManager = $entityManager;
		$this->metadata = $metadata;
		$this->fileManager = $fileManager;

		$this->schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->getEntityManager());
        $this->entityGenerator = new \Doctrine\ORM\Tools\EntityGenerator();

		$this->disconnectedClassMetadataFactory = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $this->disconnectedClassMetadataFactory->setEntityManager($this->getEntityManager());   // $em is EntityManager instance
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

	protected function getSchemaTool()
	{
		return $this->schemaTool;
	}

	protected function getDisconnectedClassMetadataFactory()
	{
		return $this->disconnectedClassMetadataFactory;
	}

	protected function getEntityGenerator()
	{
		return $this->entityGenerator;
	}



	public function setMeta(array $meta)
	{
    	$this->meta = $meta;
	}

    protected function getMeta()
	{
    	return $this->meta;
	}

	protected function getFieldMeta($type = '')
	{
		$meta = $this->getMeta();
		if (empty($type)) {
        	return $meta['fields'];
		}
		else if (isset($meta['fields'][$type])) {
        	return $meta['fields'][$type];
		}

		return false;
	}


	/**
	* Metadata conversion from Espo format into Doctrine
    *
	* @param object $meta
	*
	* @return bool
	*/
	public function convertToDoctrine()
	{
		$GLOBALS['log']->add('Debug', 'Metadata:get() - converting to doctrine metadata');

		$meta = $this->getMetadata()->get();

		return;   //TODO
    	$this->setMeta($meta);

		$cacheDir = $this->getMetadata()->getMetaConfig()->doctrineCache;
		$this->getFileManager()->removeFilesInDir($cacheDir); //remove all existing files

		//create files named like "Espo.Entities.User.php"

		$convertedMeta = array();
        foreach($meta[$this->getMetadata()->getMetaConfig()->espoMetadataName] as $entityName => $metaRow) {

			$convertedMeta[$entityName] = $this->convert($entityName, $metaRow);
			//$convertedMeta = Util::merge($convertedMeta, $this->convert($entityName, $metaRow));   //for link is need to define for two entities at once
        }


        /*echo '<pre>';
		print_r($convertedMeta);
        exit;*/

		//save doctrine meta to files
		$result= true;
		foreach ($convertedMeta as $entityName => $doctineMeta) {
			$entityFullName = $this->getMetadata()->getEntityPath($entityName);
			$doctrineMeta = array($entityFullName => $doctineMeta);

            //create a doctrine metadata file
			$fileName = str_replace('\\', '.', $entityFullName).'.php';
            $result &= $this->getFileManager()->setContent($this->getFileManager()->getPHPFormat($doctrineMeta), $cacheDir, $fileName);
			//END: create a doctrine metadata file
		}
		//END: save doctrine meta to files

        return $result;
	}


	/**
	* Rebuild a database accordinly to metadata
    *
	* @return bool
	*/
	public function rebuildDatabase()
	{
		$GLOBALS['log']->add('DEBUG', 'EspoConverter:rebuildDatabase() - start rebuild database');

	    $classes = $this->getDisconnectedClassMetadataFactory()->getAllMetadata();
		$this->getSchemaTool()->updateSchema($classes);

		$GLOBALS['log']->add('DEBUG', 'EspoConverter:rebuildDatabase() - end rebuild database');

		return true;  //always true, because updateSchema just returns the VOID
	}

	/**
	* Rebuild a database accordinly to metadata
    *
	* @return bool
	*/
	public function generateEntities($classNames)
	{
    	if (!is_array($classNames)) {
    		$classNames= (array) $classNames;
    	}

		$metadata= array();
		foreach($classNames as $className) {
        	$metadata[]=  $this->getDisconnectedClassMetadataFactory()->getMetadataFor($className);
		}

		if (!empty($metadata)) {
        	$GLOBALS['log']->add('DEBUG', 'EspoConverter:generateEntities() - start generate Entities');

		    $this->getEntityGenerator()->setGenerateAnnotations(false);
		    $this->getEntityGenerator()->setGenerateStubMethods(true);
		    $this->getEntityGenerator()->setRegenerateEntityIfExists(false);
		    $this->getEntityGenerator()->setUpdateEntityIfExists(false);
		    $this->getEntityGenerator()->generate($metadata, 'application');

			$GLOBALS['log']->add('DEBUG', 'EspoConverter:generateEntities() - end generate Entities');

			return true; //always true, because generate just returns the VOID
		}

		return false;
	}


	/**
	* Metadata conversion from Espo format into Doctrine
    *
	* @param string $name
	* @param array $data
	*
	* @return array
	*/
	//NEED TO CHANGE
	protected function convert($entityName, $meta)
	{
		if (empty($meta)) {
	    	$GLOBALS['log']->add('ERROR', 'EspoConverter:convert(), Entity:'.$entityName.' - metadata cannot be converted into Doctrine format');
		}

		//conversion functionality
		$outputMeta = array(
			'type' => 'entity',
			'table' => $entityName, //TODO: if need to convert to underscore
			'id' => array(
				'id' => array(
			        'type' => 'string',
			        'generator' => array('strategy' => 'UUID'),
				)
			),
			'fields' => array(
			),
		);

		$outputMeta = Util::merge($outputMeta, $this->convertFields($entityName, $meta));
		//$outputMeta = Util::merge($outputMeta, $this->convertLinks($meta));
		//END: conversion functionality


        return $outputMeta;
	}



	/*It can be moved to separate file*/
	protected function convertFields($entityName, array $meta)
	{
		$metaFields = $meta['fields'];

		$convertedMetaFields = array();
		foreach($meta['fields'] as $fieldName => $fieldParams) {

			//set default type if exists
        	if (!isset($fieldParams['type']) || !empty($fieldParams['type'])) {
        		$GLOBALS['log']->add('WARNING', 'Field type does not exist for '.$entityName.':'.$fieldName.'. Use default type ['.$this->defaultFieldType.']');
				$fieldParams['type'] = $this->defaultFieldType;
        	} //END: set default type if exists

			$fieldMeta = $this->getFieldMeta($fieldParams['type']);

			//check if field need to be saved in database
        	if ( (isset($fieldParams['db']) && $fieldParams['db'] === false) || (isset($fieldMeta['database']['db']) && !$fieldMeta['database']['db'] === false) ) {
        		continue;
        	} //END: check if field need to be saved in database

            $convertedMetaFields[$fieldName] = $this->getInitValues($fieldParams);


			/*echo '<pre>';
			print_r($convertedMetaFields[$fieldName]);
			exit;*/

            //convert type
            $convertedMetaFields[$fieldName]['type'] = $this->getFieldType($fieldParams['type']);
			//END: convert type
		}


        /*echo '<br />'.$entityName.'<pre>';
		print_r($convertedMetaFields); */
	}

    protected function getFieldType($espoType)
	{
		$fieldMeta = $this->getFieldMeta($espoType);

        if (isset($fieldMeta['database']['type']) && !empty($fieldMeta['database']['type'])) {
           	return $fieldMeta['database']['type'];
		}

		return $espoType;
	}

	protected function getInitValues(array $fieldParams)
	{
		//pair espo:doctrine
		$convertRules = array(
			'type' => 'type',
			'maxLength' => 'length',
			'default' => array(
			   'condition' => '!(^javascript)',
			   'json' => '{"options":{"default":{0}}}',
			),
		);


		$values = array();
		foreach($convertRules as $espoType => $doctrineType) {

        	if (isset($fieldParams[$espoType]) && !empty($fieldParams[$espoType])) {

				if (is_array($doctrineType))  {

					//print_r($fieldParams);
					//echo  $doctrineType['condition'].'    '.$fieldParams[$espoType];

                	//if (preg_match('/'.$doctrineType['condition'].'/i', $fieldParams[$espoType])) {
                		$jsonValue = json_encode($fieldParams[$espoType]);
						$jsonRes = str_replace('{0}', $jsonValue, $doctrineType['json']);
                        $values = Util::merge($values, json_decode($jsonRes, true));
                	//}
				} else {
                	$values[$doctrineType] = $fieldParams[$espoType];
				}
			}
		}

		return $values;
	}




	protected function convertLinks(array $meta)
	{
		$metaFields = $meta['links'];

	}

}


?>