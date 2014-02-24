<?php

namespace Espo\Core\Utils;

class Route
{
	protected $fileName = 'routes.json';
	protected $cacheFile = 'data/cache/application/routes.php';

	protected $data = null;

	private $fileManager;
	private $config;

	public function __construct(Config $config, File\Manager $fileManager)
	{
		$this->config = $config;
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

	protected function unify($isCustom = false)
	{
        $dirName = $isCustom ? '/Custom' : '';

	   	$data = array();

		$moduleDir = 'application/Espo'.$dirName.'/Modules';
		if (file_exists($moduleDir)) {
        	$dirList= $this->getFileManager()->getFileList($moduleDir, false, '', 'dir');

			foreach($dirList as $currentDirName) {

                $dirNameFull = Util::concatPath($moduleDir, $currentDirName);
				$routeFile = Util::concatPath($dirNameFull, 'Resources/'.$this->fileName);

				$data = $this->getAddData($data, $routeFile);
			}
		}

		//if need this path to high priority, move up this code
		$routeFile = Util::concatPath('application/Espo'.$dirName.'/Resources', $this->fileName);
        $data = $this->getAddData($data, $routeFile);

		if (!$isCustom) {
        	$data = $this->addToData($this->unify(true), $data);
		}

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