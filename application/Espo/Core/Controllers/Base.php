<?php

namespace Espo\Core\Controllers;

class Base
{
	private $container;
	private $serviceFactory;

	public function __construct(\Espo\Core\Container $container, \Espo\Core\ServiceFactory $serviceFactory)
	{
		$this->container = $container;
		$this->serviceFactory = $serviceFactory;
	}

	protected function getContainer()
	{
		return $this->container;
	}

	protected function getServiceFactory()
	{
		return $this->serviceFactory;
	}



    public function read($params, $data)
	{

	}

	public function update($params, $data)
	{

	}

	public function patch($params, $data)
	{

	}

	public function create($params, $data)
	{

	}

	public function delete($params, $data)
	{

	}


}


?>