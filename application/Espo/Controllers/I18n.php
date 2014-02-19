<?php

namespace Espo\Controllers;

class I18n extends \Espo\Core\Controllers\Base
{

    public function actionRead($params, $data)
	{
		return $this->getContainer()->get('i18n')->getAll();
	}
}
