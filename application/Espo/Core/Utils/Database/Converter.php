<?php

namespace Espo\Core\Utils\Database;

use Espo\Core\Utils\Util,
	Espo\ORM\Entity;

class Converter
{
	private $metadata;

	private $fileManager;

	private $schemaConverter;



	private $schemaFromMetadata = null;

	/**
	* @var array $meta - metadata array
	*/
	//private $meta;


    public function __construct(\Espo\Core\Utils\Metadata $metadata, \Espo\Core\Utils\File\Manager $fileManager)
	{
		$this->metadata = $metadata;
		$this->fileManager = $fileManager;

        $this->ormConverter = new Orm\Converter($this->metadata, $this->fileManager);

        $this->schemaConverter = new Schema\Converter($this->fileManager);
	}


	protected function getMetadata()
	{
		return $this->metadata;
	}

	protected function getOrmConverter()
	{
    	return $this->ormConverter;
	}

    protected function getSchemaConverter()
	{
    	return $this->schemaConverter;
	}


	public function getSchemaFromMetadata()
	{
		if (!isset($this->schemaFromMetadata)) {
        	$ormMeta = $this->getMetadata()->getOrmMetadata();
        	$entityDefs = $this->getMetadata()->get('entityDefs');

			$schema = $this->getSchemaConverter()->process($ormMeta, $entityDefs);
			$this->schemaFromMetadata = $schema;
		}

		return $this->schemaFromMetadata;
	}

	/**
	* Main method of convertation from metadata to orm metadata and database schema
	*
	* @return bool
	*/
	public function process()
	{
		$GLOBALS['log']->debug('Orm\Converter - Start: orm convertation');

		$ormMeta = $this->getOrmConverter()->process();

		//save database meta to a file espoMetadata.php
        $result = $this->getMetadata()->setOrmMetadata($ormMeta);

        $GLOBALS['log']->debug('Orm\Converter - End: orm convertation, result=['.$result.']');

        return $result;
	}




}


?>