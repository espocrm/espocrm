<?php

namespace Espo\Controllers;


class Metadata extends \Espo\Core\Controllers\Base
{

    public function actionRead($params, $data)
	{
		$data = $this->getContainer()->get('metadata')->get(true);

       	return array($data, 'Cannot reach metadata data');
	}


	public function actionUpdate($params, $data)
	{
		$result = $this->getContainer()->get('metadata')->set($data, $params['type'], $params['scope']);

		if ($result===false) {
        	return array($result, 'Cannot save metadata data');
        }

        $data= $this->getContainer()->get('metadata')->get(true, true);
        return array($data, 'Cannot get metadata data');
	}   


}


?>
