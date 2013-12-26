<?php

namespace Espo\Controllers;

class App extends \Espo\Core\Controllers\Record
{
	public function actionUser()
	{		
		return array(
			'user' => $this->getUser()->toArray(),
			'acl' => $this->getAcl()->toArray(),
			'preferences' => array(),
		);	
	}
}
