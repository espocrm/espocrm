<?php

namespace Espo\Core;

class Container
{

	private $data = array();
	
	/**
     * Constructor
     */
    public function __construct()
    {
    
    }
    
    
    public function get($name)
    {
    	if (!empty($this->data[$name])) {
    		return $this->data[$name];
    	}
    	$this->load($name);
    	return $this->data[$name];
    }
    
    private function load($name)
    {
    	$loadMethod = 'load' . ucfirst($name);
    	if (method_exists($this, $loadMethod)) {
    		$this->$loadMethod();
    	} else {
    		// TODO external loader class (\Espo\Core\Loaders\EntityManager::load())
    	}
    }
    
    private function loadMetadata()
    {
    
    }
    
    private function loadConfig()
    {
    	$this->data['config'] = new \Espo\Utils\Configurator();
    }
    
    private function loadEntityManager()
    {
    	$this->data['entityManager'] = new \Espo\Utils\EntiryManager($this->get('config'));
    }
}
