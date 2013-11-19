<?php

namespace Espo\Controllers;

use Espo\Core\Utils as Utils;

class Settings extends \Espo\Core\Controllers\Base
{

    public function actionRead($params, $data)
	{
        $isAdmin = $this->getContainer()->get('user')->isAdmin();

		$data= $this->getContainer()->get('config')->getJsonData($isAdmin);

        return array($data, 'Cannot get settings');
	}


	public function actionPatch($params, $data)
	{  
        $isAdmin = $this->getContainer()->get('user')->isAdmin();

		$result= $this->getContainer()->get('config')->setJsonData($data, $isAdmin);

        if ($result===false) {
        	return array($result, 'Cannot save settings');
        }

        $data= $this->getContainer()->get('config')->getJsonData($isAdmin);
        return array($data, 'Cannot get settings');
	}   


}


?>
