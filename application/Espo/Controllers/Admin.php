<?php

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error,
	\Espo\Core\Exceptions\Forbidden;

class Admin extends \Espo\Core\Controllers\Base
{	
	protected function checkGlobalAccess()
	{
		if (!$this->getUser()->isAdmin()) {
        	throw new Forbidden();
		}
	}

    public function actionRebuild($params, $data)
	{
		try {
			$result = $this->getContainer()->get('schema')->rebuild();
	   	} catch (\Exception $e) {
            $result = false;
		  	$GLOBALS['log']->error('Fault to rebuild database schema'.'. Details: '.$e->getMessage());
		}

		if ($result === false) {
			throw new Error("Error while rebuilding database");
		}

		return json_encode($result);
	}

	public function actionClearCache($params, $data)
	{		
		$cacheDir = $this->getContainer()->get('config')->get('cachePath');			

		$result = $this->getContainer()->get('fileManager')->removeInDir($cacheDir);

		if ($result === false) {
			throw new Error("Error while clearing cache");
		}

		return json_encode($result);
	}

}

