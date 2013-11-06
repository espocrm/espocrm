<?php

namespace Espo\Utils\Controllers;

use Espo\Utils as Utils;

class Layout extends Controller
{

    public function read($params, $data)
	{
		$layout = new Utils\Layout();
		$data = $layout->getLayout($params['controller'], $params['name']);

		return array($data, 'Cannot get this layout', 404);
	}


	public function update($params, $data)
	{
		$layout= new Utils\Layout();
        $result= $layout->setLayout($data, $params['controller'], $params['name']);

		if ($result === false) {
			return array(false, 'Layout Saving error', 500);
		}

		return array($data, 'Cannot get this layout');
	}


	public function patch($params, $data)
	{
		$layout= new Utils\Layout();
        $result= $layout->mergeLayout($data, $params['controller'], $params['name']);

		if ($result === false) {
			return array(false, 'Layout Saving error', 500);
		}

        return array($data, 'Cannot get this layout');
	}

}


?>