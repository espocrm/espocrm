<?php

namespace Espo\Utils\Controllers;

use Espo\Utils as Utils;

class Settings extends Controller
{

    public function read($params, $data)
	{
		global $base;
		$config= new Utils\Configurator();

		$isAdmin= false;
		if(isset($base->currentUser) && is_object($base->currentUser)) {
        	$isAdmin= $base->currentUser->isAdmin();
		}

		$data= $config->getJSON($isAdmin);

        return array($data, 'Cannot get settings');
	}


	public function patch($params, $data)
	{  
		global $base;
		$config= new Utils\Configurator();

		$isAdmin= false;
		if(isset($base->currentUser) && is_object($base->currentUser)) {
        	$isAdmin= $base->currentUser->isAdmin();
		}

		$result= $config->setJSON($data, $isAdmin);

        if ($result===false) {
        	return array($result, 'Cannot save settings');
        }

        $data= $config->getJSON($isAdmin);
        return array($data, 'Cannot get settings');
	}   


}


?>