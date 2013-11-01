<?php

namespace Espo\Core;

use Doctrine\ORM\Tools\Setup,
	Espo\Utils\Doctrine\ORM\Mapping\Driver\EspoPHPDriver,
	Espo\Utils as Utils;

class Base
{

	/**
	* @var EntityManager of doctrine
	*/
	public $em;

	/**
	* @var Object of Configurator
	*/
	public $utils;

	/**
	* @var Object of Log
	*/
	public $log;

	/**
	* @var Object of config
	*/
	public $config;

	/**
	* @var Object of config
	*/
	public $currentUser;


	/**
     * Constructor
     */
    protected function __construct()
    {
        $this->config = $this->getUtils()->getObject('Configurator');
        $this->em = $this->getEntityManager();
        /*$doctrineConfig->em->getConfiguration()->getMetadataDriverImpl()->addPaths(array(
            "Modules"
        ));*/

        $this->log = $this->getUtils()->getObject('Log');
    }

	/**
	* Start the app
	*/
	public static function start()
	{
        return new Base();
	}

	/**
     * Create or get an EntityManager object
     *
     */
	private function getEntityManager()
	{
		if (isset($this->em) && is_object($this->em)) {
        	return $this->em;
		}

		//EspoPHPDriver for Doctrine
		$devMode= !$this->config->get('useCache');
		$doctrineConfig = Setup::createConfiguration($devMode, null, null);
		$doctrineConfig->setMetadataDriverImpl(new EspoPHPDriver(
						array(
							$this->config->get('metadataConfig')->doctrineCache,
							$this->config->get('defaultsPath').'/doctrine/metadata'
						)
		));
		//END: EspoPHPDriver for Doctrine

		$doctrineConn = (array) $this->config->get('database');

		// obtaining the entity manager
		return \Doctrine\ORM\EntityManager::create($doctrineConn, $doctrineConfig);
	}

	/**
     * Create or get a Configurator object
     *
     */
	private function getUtils()
	{
		if (isset($this->utils) && is_object($this->utils)) {
        	return $this->utils;
		}

        return new Utils\BaseUtils();
	}

}


?>