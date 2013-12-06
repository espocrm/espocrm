<?php

namespace Espo\Core\Doctrine;

class Helper
{
	private $entityManager;

	private $schemaTool;

	private $disconnectedClassMetadataFactory;

	private $entityGenerator;


	public function __construct(\Doctrine\ORM\EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;

		$this->schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->getEntityManager());
        $this->entityGenerator = new \Doctrine\ORM\Tools\EntityGenerator();

		$this->disconnectedClassMetadataFactory = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $this->disconnectedClassMetadataFactory->setEntityManager($this->getEntityManager());   // $em is EntityManager instance
	}

	protected function getEntityManager()
	{
		return $this->entityManager;
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


}