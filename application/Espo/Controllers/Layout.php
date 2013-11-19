<?php

namespace Espo\Controllers;

use Espo\Core\Utils as Utils;

class Layout extends \Espo\Core\Controllers\Base
{

    public function actionRead($params, $data)
	{
		$data = $this->getContainer()->get('layout')->get($params['controller'], $params['name']);

		return array($data, 'Cannot get this layout', 404);
	}


	public function actionUpdate($params, $data)
	{
        $result= $this->getContainer()->get('layout')->set($data, $params['controller'], $params['name']);

		if ($result === false) {
			return array(false, 'Layout Saving error', 500);
		}

		$data = $this->getContainer()->get('layout')->get($params['controller'], $params['name']);

		return array($data, 'Cannot get this layout');
	}


	public function actionPatch($params, $data)
	{
        $result= $this->getContainer()->get('layout')->merge($data, $params['controller'], $params['name']);

		if ($result === false) {
			return array(false, 'Layout Saving error', 500);
		}

		$data = $this->getContainer()->get('layout')->get($params['controller'], $params['name']);

        return array($data, 'Cannot get this layout');
	}

}


?>
