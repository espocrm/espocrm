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
    	if (empty($this->data[$name])) {
    		$this->load($name);
    	}    	
    	return $this->data[$name];
    }

    private function load($name)
    {
    	$loadMethod = 'load' . ucfirst($name);
    	if (method_exists($this, $loadMethod)) {
    		$obj = $this->$loadMethod();
    		$this->data[$name] = $obj;
    	} else {
            //external loader class \Espo\Core\Loaders\<className> or \Espo\Custom\Core\Loaders\<className> with load() method
			$className = '\Espo\Custom\Core\Loaders\\'.ucfirst($name);
            if (!class_exists($className)) {
            	$className = '\Espo\Core\Loaders\\'.ucfirst($name);
            }

			if (class_exists($className)) {
            	 $loadClass = new $className($this);
				 $this->data[$name] = $loadClass->load();
			}
    	}

		// TODO throw an exception
    	return null;
    }    
    
    private function loadSlim()
    {
        //return new \Slim\Slim();
        return new \Espo\Core\Utils\Api\Slim();
    }

	private function loadFileManager()
    {
    	return new \Espo\Core\Utils\File\Manager(
			array(
				'defaultPermissions' => $this->get('config')->get('defaultPermissions'),				
			)
		);
    }
    
	private function loadPreferences()
    {    	
    	return $this->get('entityManager')->getEntity('Preferences', $this->get('user')->id);
    }

	private function loadConfig()
    {
    	return new \Espo\Core\Utils\Config(
			new \Espo\Core\Utils\File\Manager()
		);
    }
    
	private function loadHookManager()
    {
    	return new \Espo\Core\HookManager(
			$this
		);
    }

	private function loadOutput()
    {
    	return new \Espo\Core\Utils\Api\Output(
			$this->get('slim')
		);
    }
    
	private function loadMailSender()
    {
    	return new \Espo\Core\Mail\Sender(
			$this->get('config')
		);
    }
    
	private function loadServiceFactory()
    {
    	return new \Espo\Core\ServiceFactory(
			$this
		);
    }
    
	private function loadSelectManagerFactory()
    {
    	return new \Espo\Core\SelectManagerFactory(
			$this->get('entityManager'),
			$this->get('user'),
			$this->get('acl'),
			$this->get('metadata')
		);
    }

	private function loadMetadata()
    {
    	return new \Espo\Core\Utils\Metadata(
			$this->get('config'),
			$this->get('fileManager')
		);
    }

	private function loadLayout()
    {
    	return new \Espo\Core\Utils\Layout(			
			$this->get('fileManager'),
			$this->get('metadata')
		);
    } 
    
	private function loadAcl()
	{
		return new \Espo\Core\Acl(
			$this->get('user'),
			$this->get('config'),
			$this->get('fileManager')
		);
	}

	private function loadSchema()
	{
		return new \Espo\Core\Utils\Database\Schema\Schema(
			$this->get('config'),
			$this->get('metadata'),
			$this->get('fileManager'),
			$this->get('entityManager'),
			$this->get('classParser')
		);
	}

	private function loadClassParser()
	{
		return new \Espo\Core\Utils\File\ClassParser(
			$this->get('fileManager'),
			$this->get('config'),
			$this->get('metadata')
		);
	}

	private function loadI18n()
	{
		return new \Espo\Core\Utils\I18n(
			$this->get('fileManager'),
			$this->get('config'),
			$this->get('preferences')
		);
	}
	
	public function setUser($user)
	{
		$this->data['user'] = $user;
	}
}

