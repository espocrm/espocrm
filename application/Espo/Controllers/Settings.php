<?php

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;

class Settings extends \Espo\Core\Controllers\Base
{

    public function actionRead($params, $data)
	{
        $admin = false;
		if ($this->getUser() instanceof \Espo\Entities\User) {
           $admin = $this->getUser()->isAdmin();
		}

		return $this->getConfig()->getJsonData($admin);
		//return $this->getConfig()->getJsonData($this->getUser()->isAdmin());
	}

	public function actionPatch($params, $data)
	{
    	$admin = false;
		if ($this->getUser() instanceof \Espo\Entities\User) {
           $admin = $this->getUser()->isAdmin();
		}

        $result = $this->getConfig()->setJsonData($data, $admin);
        if ($result === false) {
        	throw new Error('Cannot save settings');
        }
        return $this->getConfig()->getJsonData($admin);

		/*$result = $this->getConfig()->setJsonData($data, $this->getUser()->isAdmin());
        if ($result === false) {
        	throw new Error('Cannot save settings');
        }
        return $this->getConfig()->getJsonData($this->getUser()->isAdmin());*/
	}
}
