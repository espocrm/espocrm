<?php

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;

class Rebuild extends \Espo\Core\Controllers\Base
{

    public function actionRead($params, $data)
	{
		try{
			$result = $this->getContainer()->get('schema')->rebuild();
	   	} catch (\Exception $e) {
            $result = false;
		  	$GLOBALS['log']->add('EXCEPTION', 'Fault to rebuild database schema'.'. Details: '.$e->getMessage());
		}

		if ($result === false) {
			throw new Error("Error while rebuilding database");
		}

		return json_encode($result);
	}

}
