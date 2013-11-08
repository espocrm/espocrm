<?php

namespace Espo\Core;

class Application
{

	private $metadata;
	
	private $container;
	
	private $serviceFactory;	

	
	/**
     * Constructor
     */
    public function __construct()
    {    
    	    	    	
    	$this->container = new Container();
    	
    	$this->metadata = $this->container->get('metadata');	
    	
    	$this->serviceFactory = new ServiceFactory($this->container);  
    	
    	//$this->slim = new \Slim\  
    }
    
    public function run($name)
    {
    
    
    	// TODO place routing HERE    	
    	// dispatch which controller to user   	
    	// $this->controller = new $controllerClassName($this->container, $this->serviceFactory);
    	// call needed controller method $this->$method($params, $data)
    	
    	
    
		// dont't return anything here
    }    

}
