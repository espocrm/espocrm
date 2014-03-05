<?php

namespace Espo\Core\Utils;

class Route
{
	protected $data = null;

	private $fileManager;
	private $config;
	private $metadata;

	protected $cacheFile = 'data/cache/application/routes.php';

	protected $paths = array(
		'corePath' => 'application/Espo/Resources/routes.json',
    	'modulePath' => 'application/Espo/Modules/{*}/Resources/routes.json',
    	'customPath' => 'custom/Espo/Custom/Resources/routes.json',	                              			
	);

	public function __construct(Config $config, Metadata $metadata, File\Manager $fileManager)
	{
		$this->config = $config;
		$this->metadata = $metadata;
		$this->fileManager = $fileManager;
	}

	protected function getConfig()
	{
		return $this->config;
	}

	protected function getFileManager()
	{
		return $this->fileManager;
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}


	public function get($key = '', $returns = null)
	{
		if (!isset($this->data)) {
			$this->init();
		}

		if (empty($key)) {
        	return $this->data;
        }

		$keys = explode('.', $key);

		$lastRoute = $this->data;
		foreach($keys as $keyName) {
        	if (isset($lastRoute[$keyName]) && is_array($lastRoute)) {
            	$lastRoute = $lastRoute[$keyName];
        	} else {
        		return $returns;
        	}
		}

		return $lastRoute;
	}


	public function getAll()
	{
		return $this->get();
	}


	protected function init()
	{
		if (file_exists($this->cacheFile) && $this->getConfig()->get('useCache')) {
			$this->data = $this->getFileManager()->getContents($this->cacheFile);
		} else {
        	$this->data = $this->unify();

			$result = $this->getFileManager()->putContentsPHP($this->cacheFile, $this->data);
			 if ($result == false) {			
	         	throw new \Espo\Core\Exceptions\Error('Route - Cannot save unified routes');
			 }
		}
	}

	protected function unify()
	{
		$data = array();

		$data = $this->getAddData($data, $this->paths['customPath']);		

    	foreach ($this->getMetadata()->getModuleList() as $moduleName) {
    		$modulePath = str_replace('{*}', $moduleName, $this->paths['modulePath']);    		
			$data = $this->getAddData($data, $modulePath);
    	}

    	$data = $this->getAddData($data, $this->paths['corePath']);

		return $data;
	}

	protected function getAddData($currData, $routeFile)
	{
		if (file_exists($routeFile)) {
        	$content= $this->getFileManager()->getContents($routeFile);
			$arrayContent = Json::getArrayData($content);
			if (empty($arrayContent)) {
            	$GLOBALS['log']->error('Route::unify() - Empty file or syntax error - ['.$routeFile.']');
				return $currData;
			}

	        $currData = $this->addToData($currData, $arrayContent);
		}

        return $currData;
	}


	protected function addToData($data, $newData)
	{
		if (!is_array($newData)) {
			return $data;
		}

		foreach($newData as $route) {  
			
			$route['route'] = $this->adjustPath($route['route']);
			
        	$data[] = $route;               
		}

        return $data;
	}

    /**
     * Check and adjust the route path
	 *
	 * @param string $routePath - it can be "/App/user",  "App/user"
	 *
	 * @return string - "/App/user"
	 */
    protected function adjustPath($routePath)
    {
		$routePath = trim($routePath);

		if ( substr($routePath,0,1) != '/') {
			return '/'.$routePath;	
		}

		return $routePath;
    }

}