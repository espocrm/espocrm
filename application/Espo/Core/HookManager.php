<?php

namespace Espo\Core;

use \Espo\Core\Exceptions\Error,
	\Espo\Core\Utils\Util;

class HookManager
{	
	private $container;
	
	private $data;
	
	private $hooks;
	
	protected $cacheFile = 'data/cache/application/hooks.php';

	/**
     * List of defined hooks
     *
     * @var array
     */
	protected $hookList = array(
		'beforeSave',
		'afterSave',
	);

    public function __construct(Container $container)
    {
    	$this->container = $container;
    	$this->loadHooks();     	
    }
    
    protected function getConfig()
    {
    	return $this->container->get('config');
    }

	protected function getFileManager()
	{
		return $this->container->get('fileManager');
	}

    protected function loadHooks()
    {
    	if ($this->getConfig()->get('useCache') && file_exists($this->cacheFile)) {
    		$this->data = $this->getFileManager()->getContent($this->cacheFile);
    		return;
    	} 
    	
    	$metadata = $this->container->get('metadata');

		$this->data = $this->getHookData( array('Espo/Hooks', 'Espo/Custom/Hooks') );
    	foreach ($metadata->getModuleList() as $moduleName) {
			$this->data = array_merge($this->data, $this->getHookData( array('Espo/Modules/'.$moduleName.'/Hooks', 'Espo/Custom/Modules/'.$moduleName.'/Hooks') ));
    	}   	


    	if ($this->getConfig()->get('useCache')) {
			$this->getFileManager()->setContentPHP($this->data, $this->cacheFile);
    	}
    }
    
    public function process($scope, $hookName, $injection = null)
    {	
    	if (!empty($this->data[$scope])) {
    		if (!empty($this->data[$scope][$hookName])) {
    			foreach ($this->data[$scope][$hookName] as $className) {
    				if (empty($this->hooks[$className])) {
    					$this->hooks[$className] = $this->createHookByClassName($className);
    				} 
    				$hook = $this->hooks[$className];    				
    				$hook->$hookName($injection);				
    			}
    		}
    	}    	
    }
	
	public function createHookByClassName($className)
	{
    	if (class_exists($className)) {
    		$hook = new $className();
    		$dependencies = $hook->getDependencyList();
    		foreach ($dependencies as $name) {
    			$hook->inject($name, $this->container->get($name));
    		}
    		return $hook;
    	}
    	throw new Error("Class '$className' does not exist");
	}

    /**
     * Get and merge hook data by checking the files exist in $hookDirs
	 *
	 * @param array $hookDirs - it can be an array('Espo/Hooks', 'Espo/Custom/Hooks', 'Espo/Custom/Modules/Crm/Hooks')
	 *
	 * @return array
	 */
	protected function getHookData(array $hookDirs)
	{
		$hooks = array();

		foreach($hookDirs as $hookDir) {

	        $fullHookDir = 'application/'.$hookDir;
			if (file_exists($fullHookDir)) {
	        	$fileList = $this->getFileManager()->getFileList($fullHookDir, 1, '\.php$', 'file');

	            foreach($fileList as $scopeName => $hookFiles) {

					$hookScopeDirPath = Util::concatPath($hookDir, $scopeName);

					$scopeHooks = array();
					foreach($hookFiles as $hookFile) {
						$hookFilePath = Util::concatPath($hookScopeDirPath, $hookFile);
	                	$className = '\\'.Util::toFormat(preg_replace('/\.php$/i', '', $hookFilePath), '\\');

						foreach($this->hookList as $hookName) {
							if (method_exists($className, $hookName)) {
								$scopeHooks[$hookName][$className::$order][] = $className;
							}
						}
					}

					//sort hooks by order
	                foreach($scopeHooks as $hookName => $hookList) {
						ksort($hookList);

						$sortedHookList = array();
						foreach($hookList as $hookDetails) {
	                    	$sortedHookList = array_merge($sortedHookList, $hookDetails);
						}

                        $hooks[$scopeName][$hookName] = isset($hooks[$scopeName][$hookName]) ? array_merge($hooks[$scopeName][$hookName], $sortedHookList) : $sortedHookList;
					}
	            }
			}

		}

		return $hooks;
	}

}

