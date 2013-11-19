<?php

namespace Espo\Core\Loaders;

use Doctrine\ORM\Tools\Setup,
	Espo\Core\Doctrine\ORM\Mapping\Driver\EspoPHPDriver;

class EntityManager
{
	private $container;


	function __construct(\Espo\Core\Container $container)
	{
		$this->container = $container;
	}

	protected function getContainer()
	{
    	return $this->container;
	}


	public function load()
	{
		//EspoPHPDriver for Doctrine
		$devMode= !$this->getContainer()->get('config')->get('useCache');
		$doctrineConfig = Setup::createConfiguration($devMode, null, null);
		$doctrineConfig->setMetadataDriverImpl(new EspoPHPDriver(
						array(
							$this->getContainer()->get('config')->get('metadataConfig')->doctrineCache,
							$this->getContainer()->get('config')->get('defaultsPath').'/doctrine/metadata'
						)
		));
		//END: EspoPHPDriver for Doctrine

		$doctrineConn = (array) $this->getContainer()->get('config')->get('database');

		// obtaining the entity manager
		return \Doctrine\ORM\EntityManager::create($doctrineConn, $doctrineConfig);
	}



}


?>