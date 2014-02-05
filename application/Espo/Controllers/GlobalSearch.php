<?php

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error,
	\Espo\Core\Exceptions\Forbidden;

class GlobalSearch extends \Espo\Core\Controllers\Base
{
    public function actionSearch($params, $data, $request)
	{
		$query = $params['query'];
		
		$offset = $request->get('offset');
		$maxSize = $request->get('maxSize');		
		
		return $this->getService('GlobalSearch')->find($query, $offset);
	}
}

