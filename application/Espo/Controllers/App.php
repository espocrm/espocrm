<?php

namespace Espo\Controllers;

class App extends \Espo\Core\Controllers\Record
{
	public function actionUser()
	{		
		return $this->getUser()->toArray();		
	}
}
