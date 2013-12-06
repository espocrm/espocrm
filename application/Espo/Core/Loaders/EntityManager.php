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
		$config = $this->getContainer()->get('config');
		
		$params = array(
			'host' => $config->get('database.host'),
			'dbname' => $config->get('database.dbname'),
			'user' => $config->get('database.user'),
			'password' => $config->get('database.password'),
		);
		
		$entityManager = new \Espo\ORM\EntityManager($params);
		
		return $entityManager;
	}
}
