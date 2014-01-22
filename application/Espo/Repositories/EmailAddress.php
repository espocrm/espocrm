<?php

namespace Espo\Repositories;

use Espo\ORM\Entity;

class EmailAddress extends \Espo\Core\ORM\Repository
{
	public function getIds($arr = array())
	{		
		$ids = array();		
		if (!empty($arr)) {
			$a = array_map(function ($item) {
					return strtolower($item);
				}, $arr);
			$eas = $this->where(array(
				'lower' => array_map(function ($item) {
					return strtolower($item);
				}, $arr)
			))->find();
			$ids = array();
			$exist = array();
			foreach ($eas as $ea) {
				$ids[] = $ea->id;
				$exist[] = $ea->get('lower');
			}
			foreach ($arr as $address) {
				if (!in_array(strtolower($address), $exist)) {
					$ea = $this->get();
					$ea->set('name', $address);
					$this->save($ea);
					$ids[] = $ea->id;
				}
			}
		}
		return $ids;
	}
}

