<?php

namespace Espo\Core\Doctrine;

class EspoConverter
{
	private $entityManager;
	private $metadata;
	private $schemaTool;
	private $disconnectedClassMetadataFactory;
	private $entityGenerator;

    public function __construct(\Doctrine\ORM\EntityManager $entityManager, \Espo\Core\Utils\Metadata $metadata)
	{
		$this->entityManager = $entityManager;
		$this->metadata = $metadata;

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
	* @param bool $withName - different return array. If $withName=false, then return is "array()"; $withName=true, then array('name'=>$entityName, 'meta'=>$doctrineMeta);
	*
	* @return array
	*/
	//NEED TO CHANGE
	function convert($name, $data, $withName=false)
	{
		//HERE SHOULD BE CONVERSION FUNCTIONALITY
		$entityFullName= $this->getMetadata()->getEntityPath($name, '\\');   

		$doctrineMeta= array(
            $entityFullName => $data
		);

		if ($withName) {
			return array('name'=>$entityFullName, 'meta'=>$doctrineMeta);
		}
        return $doctrineMeta;
	}

}


?>