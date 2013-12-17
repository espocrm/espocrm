<?php

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error,
	\Espo\Core\Exceptions\Forbidden;

class Admin extends \Espo\Core\Controllers\Base
{

	public function __construct(Container $container, ServiceFactory $serviceFactory)
	{
		parent::__construct($container, $serviceFactory);

		if (!$this->getUser()->isAdmin()) {
        	throw new Forbidden("You do not have access to this area");
		}
	}
	

    public function actionRebuild($params, $data)
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
