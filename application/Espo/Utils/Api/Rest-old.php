<?php

namespace Espo\Utils\Api;

use \Slim\Slim,
	\Espo\Utils as Utils;

class Rest extends BaseUtils
{

    /**
	* @var string Layout folder path
	*/
	static $layoutPath= 'application/layouts';
	static $metadataPath= 'application/metadata';
	static $cachePath= 'data/cache';

	static $exModules= array(
				'lead',
			);
	static $defaultResponce= array(
				'status' => 'OK',
				'responce' => 'default response',
			);

	//DIRECTORY_SEPARATOR


	function main()
	{
	    $template = <<<EOT
	            <h1>Main Page of REST API!!!</h1>
EOT;
	    self::output($template);
	}

	function appAction($action)
	{
		$returns= array('action' => $action);
		self::output(json_encode($returns));
	}

	function getControllerList($controller)
	{
		$sugarRest= self::sugarConnect(true, $controller);

		if (in_array($controller, self::$exModules)) {
        	self::output(json_encode(self::$defaultResponce));
		}

		$soap_params= array(
	         'module_name'=>$controller,
	         'fields'=>array(),
	    );
	    $fields= $sugarRest->call('get_module_fields', $soap_params);

		$filter_fields= array_keys($fields['module_fields']);
		$controller_result= $sugarRest->getList($controller, $filter_fields, '', '0', '10');

		self::output(json_encode($controller_result));
	}

	function getController($controller, $id, $echo=1)
	{
		$sugarRest= self::sugarConnect(true, $controller);

		if (in_array($controller, self::$exModules)) {
        	self::output(json_encode(self::$defaultResponce));
		}

	   	$where= " ".strtolower($controller).".id = '".$id."'";
		$soap_params= array(
	         'module_name'=>$controller,
	         'fields'=>array(),
	    );
	    $fields= $sugarRest->call('get_module_fields', $soap_params);

		$filter_fields= array_keys($fields['module_fields']);
		$controller_result= $sugarRest->getList($controller, $filter_fields, $where, '0', '10');

		if (!$echo) {
			return $controller_result;
		}

		self::output(json_encode($controller_result));
	}

    function getLayout($controller, $type, $echo=1)
	{
		if (!$data = self::getFromFile(self::$layoutPath, $controller, $type)) {
        	return false;
		}

        if (!$echo) {
			return $data;
		}

		self::output($data);
	}

	function putLayout($controller, $type)
	{
		$app= Slim::getInstance();
		$data = $app->request()->getBody();

		if (self::saveToFile($data, self::$layoutPath, $controller, $type)) {
        	self::output($data);
		}
		else {
            self::error('Layout Problem');
		}
	}

	function patchLayout($controller, $type)
	{
    	if (!$savedData = self::getFromFile(self::$layoutPath, $controller, $type)) {
        	return false;
		}
		if (empty($savedData)) {
			$savedData= '{}';
		}
		$savedDataArray= json_decode($savedData, true);

		$app= Slim::getInstance();
		$newData = $app->request()->getBody();

        $newDataArray= json_decode($newData, true);

        $data= json_encode(array_merge($savedDataArray, $newDataArray));

		if (self::saveToFile($data, self::$layoutPath, $controller, $type)) {
        	self::output($data);
		}
		else {
            self::error('Layout Problem');
		}
	}

	//new
	function putMetadata($type, $scope)
	{
		$app= Slim::getInstance();
		$data = $app->request()->getBody();

		//die('Here');

		if (self::saveToFile($data, self::$metadataPath, $type, $scope)) {
        	self::output($data);
		}
		else {
            self::error('Save Metadata Problem');
		}
	}

	//new
	function getMetadata($type, $scope)
	{
		if (!$data = self::getFromFile(self::$metadataPath, $type, $scope)) {
        	return false;
		}

		self::output($data);
	}

	function getMetadataByType($type)
	{
		//$folderPath= implode('/', array(self::$metadataPath, $type));
        $folderPath= self::$metadataPath.'/'.$type;

		require_once('include/FileManager/FileManager.php');
		$Files= new FileManager();

		//check if cache metadata file exists
		$cacheFile= self::$cachePath .$Files->getSeparator(). $folderPath . $Files->getFileExt();
		if (file_exists($cacheFile)) {
			$data= $Files->getContent($cacheFile);
            self::output($data);
	    }
		//END: check if cache metadata file exists

		//merge matadata files
		$fileList= $Files->getFileList($folderPath, false);

		$content= array();
		foreach($fileList as $fileName) {
        	$fileContent= $Files->getContent($folderPath, $fileName);
			if ($fileContent) {
            	$content[]= json_decode($fileContent);
			}
		}
		$data= json_encode($content);
        //END: merge matadata files

		//save medatada to cache file
		$Files->setContent($data, $cacheFile);
		//END: save medatada to cache file

		self::output($data);
	}


	function getUserPreferences()
	{
		$module= 'Users';
		$id= '1';

		$user= self::getController($module, $id, false);

        $userJson= json_encode($user['entry_list'][$id]);
		self::output('{"user":'.$userJson.',"preferences":{}}');
	}

	function getSettings()
	{
		if (!$data = self::getFromFile(self::$layoutPath, 'app', 'settings')) {
        	return false;
		}

		self::output($data);

		/*$returns= array(
			'theme' => 'default',
			'language' => 'en',
		);

        self::output(json_encode($returns));*/
	}

	function putSettings()
	{
		$app= Slim::getInstance();
		$data = $app->request()->getBody();

		if (self::saveToFile($data, self::$layoutPath, 'app', 'settings')) {
        	self::output($data);
		}
		else {
            self::error('Saving Problem');
		}
	}


	function sugarConnect($check=false, $controller='')
	{
		require_once('api/sugar.REST.php');
		$sugarRest= new sugarRest();

		if ($check) {
        	$modules= $sugarRest->modules();
			$modules= array_merge($modules, self::$exModules);

			if (!in_array($controller, $modules)) {

                $app= Slim::getInstance();
				$app->halt(404);
				
				//echo json_encode(array('error'=>'---Module does not exist'));
				return false;
			}
		}

        return $sugarRest;
	}


	/**
    * Save layout from the PUT request
	*
	* @param string $data JSON string
	* @param string $controller Ex. Accounts, Leads
	* @param string $type Layout type, ex. list, detail, edit
	*
	* @return bool
	*/
	/*function saveLayoutToFile($data, $controller, $type)
	{
		$folderPath= self::$layoutPath.'/'.$controller;

		if (!file_exists($folderPath)) {
	        if (!mkdir($folderPath, 0775)) {
	        	self::error('Permission denied: unable to generate a folder on the server - '.$folderPath);
	        }
	    }

		$filePath= $folderPath.'/'.$type;
		return file_put_contents($filePath, $data);
	}*/


	/**
    * Get layout from the saved file
	*
	* @param string $controller Ex. Accounts, Leads
	* @param string $type Layout type, ex. list, detail, edit
	*
	* @return string JSON string
	*/
	/*function getLayoutFromFile($controller, $type)
	{
		$filePath= self::$layoutPath.'/'.$controller.'/'.$type;

		if (file_exists($filePath)) {
            return file_get_contents($filePath);
		}

		$filePath= self::$layoutPath.'/default/'.$type;
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
		}

        return false;
	} */

	/**
    * Save content to file from the PUT request
	*
	* @param string $data JSON string
	* @param string $folderPath - Ex. layouts, metadata
	* @param string $folderName - Ex. Accounts, Leads, defs
	* @param string $fileName - Ex. list, detail, edit, contact.json
	*
	* @return bool
	*/
	function saveToFile($data, $folderPath, $folderName, $fileName)
	{
		$currentFolderPath= $folderPath.'/'.$folderName;
		if (empty($folderName)) {
        	$currentFolderPath= $folderPath;
		}

		if (!file_exists($currentFolderPath)) {
	        if (!mkdir($currentFolderPath, 0775)) {
	        	self::error('Permission denied: unable to generate a folder on the server - '.$currentFolderPath);
	        }
	    }

		$filePath= $currentFolderPath.'/'.$fileName;
		return file_put_contents($filePath, $data);
	}

	/**
    * Get content from the saved file
	*
	* @param string $folderPath - Ex. layouts, metadata
	* @param string $folderName - Ex. Accounts, Leads, defs
	* @param string $fileName - Ex. list, detail, edit, contact.json
	*
	* @return string JSON string
	*/
	function getFromFile($folderPath, $folderName, $fileName)
	{
		$filePath= $folderPath.'/'.$folderName.'/'.$fileName;
		if (empty($folderName)) {
        	$filePath= $folderPath.'/'.$fileName;
		}

		if (file_exists($filePath)) {
            return file_get_contents($filePath);
		}

		$filePath= $folderPath.'/default/'.$fileName;
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
		}

        return false;
	}

    function output($data, $jsonConvert=false)
	{
		ob_clean();
		if ($jsonConvert) {
        	$data= json_encode($data);
		}
    	echo $data;

		$app= Slim::getInstance();
		$app->stop();
	}

	function error($text)
	{
		self::output('{"error":{"text":'.$text.'}}');
	}

}

/*
function appAction($action)
{
	try {
		$returns= array('action' => $action);
		echo json_encode($returns);
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}
*/


?>