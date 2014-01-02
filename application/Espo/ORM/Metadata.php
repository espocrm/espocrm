<?php

namespace Espo\ORM;

class Metadata
{
	protected $data = array();
	
	public function setData($data)
	{
		$this->data = $data;
	}
	
	public function get($entityName)
	{
		return $this->data[$entityName];
	}

}


