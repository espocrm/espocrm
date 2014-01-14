<?php

namespace Espo\Core\Utils\Database\Relations;

use Espo\Core\Utils\Util;

class ForeignType
{

	public function load($params, $foreignParams)
	{
        if (!is_array($params['link']['foreign'])) {
        	$params['link']['foreign'] = (array) $params['link']['foreign'];
        }

		$usForeignEntity = Util::toUnderScore($params['link']['entity']);

		$orderBy = array();
		foreach($params['link']['foreign'] as $foreignField) {
        	if (trim($foreignField) != '') {
            	$orderBy[] = $usForeignEntity.'_f.'.Util::toUnderScore($foreignField).' {direction}';
        	}
		}     

		return array(
			$params['entityName'] => array(
				'fields' => array(
					$params['link']['name'] => array(
						'orderBy' => implode(', ', $orderBy),
					),
				),
			),
		);
	}

}
