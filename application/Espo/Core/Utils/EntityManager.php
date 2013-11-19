<?php

namespace Espo\Core\Utils;

use Doctrine\ORM\Tools\Setup,
	Espo\Core\Doctrine\ORM\Mapping\Driver\EspoPHPDriver;

class EntityManager
{
	private $config;


	function __construct(\Espo\Core\Utils\Config $config)
	{
		$this->config = $config;
	}

	public function getConfig()
	{
    	return $this->config;
	}


	public function create()
	{
		//EspoPHPDriver for Doctrine
		$devMode= !$this->getConfig()->get('useCache');
		$doctrineConfig = Setup::createConfiguration($devMode, null, null);
		$doctrineConfig->setMetadataDriverImpl(new EspoPHPDriver(
						array(
							$this->getConfig()->get('metadataConfig')->doctrineCache,
							$this->getConfig()->get('defaultsPath').'/doctrine/metadata'
						)
		));
		//END: EspoPHPDriver for Doctrine

		$doctrineConn = (array) $this->getConfig()->get('database');

		// obtaining the entity manager
		return \Doctrine\ORM\EntityManager::create($doctrineConn, $doctrineConfig);
	}



}


?>