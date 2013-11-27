<?php

namespace Espo\Core;

use \Espo\Core\Utils\Util;
use \Espo\Core\Exceptions\NotFound;

class ControllerManager
{
	private $config;

	private $metadata;
	
	private $container;
	
	private $serviceFactory;

	public function __construct(\Espo\Core\Container $container, \Espo\Core\ServiceFactory $serviceFactory)
	{	
		$this->container = $container;
		
		$this->config = $this->container->get('config');
		$this->metadata = $this->container->get('metadata');
		$this->serviceFactory = $serviceFactory;
	}

    protected function getConfig()
	{
		return $this->config;
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}
	
	public function process($controllerName, $actionName, $params, $data)
	{		
		$customeClassName = '\\Espo\\Custom\\Controllers\\' . $controllerName;
		if (class_exists($customeClassName)) {
			$controllerClassName = $customeClassName;
		} else {
			$moduleName = $this->metadata->getScopeModuleName($controllerName);
			if ($moduleName) {
				$controllerClassName = '\\Espo\\Modules\\' . $moduleName . '\\Controllers\\' . $controllerName;
			} else {
				$controllerClassName = '\\Espo\\Controllers\\' . $controllerName;
			}
		}

		
		if (!class_exists($controllerClassName)) {
			throw new NotFound("Controller '$controllerName' is not found");
		}			
		
		$controller = new $controllerClassName($this->container, $this->serviceFactory);
		
		if ($actionName == 'index') {
			$actionName = $controller->defaultAction;
		}		
		
		$actionNameUcfirst = ucfirst($actionName);
		
		$beforeMethodName = 'before' . $actionNameUcfirst;			 
		if (method_exists($controller, $beforeMethodName)) {
			$controller->$beforeMethodName($params, $data);
		}
		$actionMethodName = 'action' . $actionNameUcfirst;
		
		if (!method_exists($controller, $actionMethodName)) {			
			throw new NotFound("Action '$actionMethodName' does not exist in controller '$controller'");
		}	
			
		$result = $controller->$actionMethodName($params, $data);
		
		$afterMethodName = 'after' . $actionNameUcfirst;	
		if (method_exists($controller, $afterMethodName)) {
			$controller->$afterMethodName($params, $data);
		}
				
		return $result;		
		
	}

}
