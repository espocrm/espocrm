<?php

namespace Espo\Core\Utils;

class Route
{
	protected $fileName = 'routes.json';
	protected $cacheFileName = 'application/routes.php';

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
		$cacheFile = Util::concatPath($this->getConfig()->get('cachePath'), $this->cacheFileName);

		if (file_exists($cacheFile) && $this->getConfig()->get('useCache')) {
			$this->data = $this->getFileManager()->getContent($cacheFile);
		} else {
        	$this->data = $this->uniteFiles();

			$result = $this->getFileManager()->setContentPHP($this->data, $cacheFile);
			 if ($result == false) {
			 	$GLOBALS['log']->add('EXCEPTION', 'Route::init() - Cannot save Routes to a file');
	         	throw new \Espo\Core\Exceptions\Error();
			 }
		}
	}

	protected function uniteFiles($isCustom = false)
	{
        $dirName = $isCustom ? '/Custom' : '';

	   	$data = array();

		$moduleDir = 'application/Espo'.$dirName.'/Modules';
		if (file_exists($moduleDir)) {
        	$dirList= $this->getFileManager()->getFileList($moduleDir, false, '', 'dir');

			foreach($dirList as $currentDirName) {

                $dirNameFull = Util::concatPath($moduleDir, $currentDirName);
				$routeFile = Util::concatPath($dirNameFull, $this->fileName);

				$data = $this->getAddData($data, $routeFile);
			}
		}

		//if need this path to high priority, move up this code
		$routeFile = Util::concatPath('application/Espo'.$dirName, $this->fileName);
        $data = $this->getAddData($data, $routeFile);

		if (!$isCustom) {
        	$data = $this->addToData($this->uniteFiles(true), $data);
		}

		return $data;
	}

	protected function getAddData($currData, $routeFile)
	{
		if (file_exists($routeFile)) {
        	$content= $this->getFileManager()->getContent($routeFile);
			$arrayContent = Json::getArrayData($content);
			if (empty($arrayContent)) {
            	$GLOBALS['log']->add('ERROR', 'Route::uniteFiles() - Empty file or syntax error - ['.$routeFile.']');
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
        	$data[] = $route;
		}

        return $data;
	}

}