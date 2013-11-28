<?php

namespace Espo\Controllers;

class Metadata extends \Espo\Core\Controllers\Base
{

    public function actionRead($params, $data)
	{
		return $this->getMetadata()->getAll(true);
	}
}
