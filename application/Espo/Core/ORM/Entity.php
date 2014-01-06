<?php

namespace Espo\Core\ORM;

class Entity extends \Espo\ORM\Entity
{
	public function loadLinkMultipleField($field)
	{
		if ($this->hasRelation($field) && $this->hasField($field . 'Ids')) {
			$collection = $this->get($field);
			$ids = array();
			$names = new \stdClass();		
			foreach ($collection as $e) {
				$id = $e->id;
				$ids[] = $id;
				$names->$id = $e->get('name');
			}			
			$this->set($field . 'Ids', $ids);
			$this->set($field . 'Names', $names);
		}
	}
}

