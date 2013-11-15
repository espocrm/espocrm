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


    private function loadSlim()
    {
    	/* START: remove for composer */
		require 'vendor/Slim/Slim.php';
		\Slim\Slim::registerAutoloader();
		/* END: remove for composer */

        $this->data['slim'] = new \Slim\Slim();
    }


	private function loadFileManager()
    {
    	$this->data['fileManager'] = new \Espo\Core\Utils\File\Manager(
			(object) array(
				'defaultPermissions' => (object)  array (
				    'dir' => '0775',
				    'file' => '0664',
				    'user' => '',
				    'group' => '',
			  ),
			)
		);
    }

	private function loadConfig()
    {
    	$this->data['config'] = new \Espo\Core\Utils\Config(
			$this->get('fileManager')
		);
    }

	private function loadLog()
    {
    	$this->data['log'] = new \Espo\Core\Utils\Log(
			$this->get('fileManager'),
			$this->get('rest'),
			$this->get('resolver'),
			(object) array(
				'options' => $this->get('config')->get('logger'),
				'datetime' => $this->get('datetime')->getDatetime(),
			)
		);
    }

	private function loadRest()
    {
    	$this->data['rest'] = new \Espo\Core\Utils\Api\Rest(
			$this->get('slim')
		);
    }

	private function loadMetadata()
    {
    	$this->data['metadata'] = new \Espo\Core\Utils\Metadata(
			$this->get('entityManager'),
			$this->get('config'),
			$this->get('fileManager'),
			$this->get('uniteFiles')
		);
    }


	private function loadLayout()
    {
    	$this->data['layout'] = new \Espo\Core\Utils\Layout(
			$this->get('config'),
			$this->get('fileManager'),
			$this->get('metadata')
		);
    }

	private function loadResolver()
    {
    	$this->data['resolver'] = new \Espo\Core\Utils\Resolver(
			$this->get('metadata')
  		);
    }

    private function loadEntityManager()
    {
		$espoEM = new \Espo\Core\Utils\EntityManager(
			$this->get('config')
		);
        $this->data['entityManager'] = $espoEM->create();
    }

	private function loadControllerManager()
    {
    	$this->data['controllerManager'] = new \Espo\Core\Controllers\Manager(
			$this->get('config'),
			$this->get('metadata')
		);
    }

	private function loadDatetime()
    {
    	$this->data['datetime'] = new \Espo\Core\Utils\Datetime(
			$this->get('config')
		);
    }    

	private function loadUniteFiles()
    {
       	$this->data['uniteFiles'] = new \Espo\Core\Utils\File\UniteFiles(
			$this->get('fileManager'),
            (object) array(
				'unsetFileName' => $this->get('config')->get('unsetFileName'),
				'defaultsPath' => $this->get('config')->get('defaultsPath'),
			)
		);
    }

	private function loadUser()
    {
       	$this->data['user'] = new \Espo\Core\Utils\User(
			$this->get('entityManager'),
			$this->get('config')
		);
    }

}
