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



    public function actionRead($params, $data)
	{

	}

	public function actionUpdate($params, $data)
	{

	}

	public function actionPatch($params, $data)
	{

	}

	public function actionCreate($params, $data)
	{

	}

	public function actionDelete($params, $data)
	{

	}


}


?>
