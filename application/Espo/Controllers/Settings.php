<?php

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;

class Settings extends \Espo\Core\Controllers\Base
{

    public function actionRead($params, $data)
	{
		return $this->getConfig()->getJsonData($this->getUser()->isAdmin());
	}

	public function actionPatch($params, $data)
	{
       	$result = $this->getConfig()->setData($data, $this->getUser()->isAdmin());
        if ($result === false) {
        	throw new Error('Cannot save settings');
        }
        return $this->getConfig()->getData($this->getUser()->isAdmin());
	}
}
