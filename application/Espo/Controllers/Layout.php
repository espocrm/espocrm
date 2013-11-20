<?php

namespace Espo\Controllers;

use Espo\Core\Utils as Utils;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Error;

class Layout extends \Espo\Core\Controllers\Base
{

    public function actionRead($params, $data)
	{
		$data = $this->getContainer()->get('layout')->get($params['controller'], $params['name']);
		if (empty($data)) {
			throw new NotFound("Layout " . $params['controller'] . ":" . $params['name'] . ' is not found');
		}
		return $data;
	}

	public function actionUpdate($params, $data)
	{
        $result = $this->getContainer()->get('layout')->set($data, $params['controller'], $params['name']);

		if ($result === false) {
			throw new Error("Error while saving layout");
		}

		return $this->getContainer()->get('layout')->get($params['controller'], $params['name']);
	}

	public function actionPatch($params, $data)
	{
        return $this->actionUpdate($params, $data);
	}
}
