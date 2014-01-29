<?php

namespace Espo\Modules\Crm\Controllers;

class InboundEmail extends \Espo\Core\Controllers\Record
{

	public function actionFetch($params, $data, $request)
	{
		$id = $request->get('id');
		try {
			$this->getRecordService()->fetchFromMailServer($id);
		} catch (\Exception $e) {
			echo $e->getMessage();
		}
		echo "--";
		die;
	}

}
