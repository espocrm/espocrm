<?php

namespace Espo\Repositories;

use Espo\ORM\Entity;

class Preferences extends \Espo\Core\ORM\Repository
{
	protected $data = array();
	
	protected $entityName = 'Preferences';
	
	public function get($id = null)
	{				
		if ($id) {
			$entity = $this->entityFactory->create('Preferences');
			if (empty($this->data[$id])) {
				$fileName = 'data/preferences/' . $id;
				if (file_exists($fileName)) {
					$this->data[$id] = include ($fileName);
				} else {
					$fields = $this->getMetadata()->get('entityDefs.Preferences.fields');
					$defaults = array();
					foreach ($fields as $field => $d) {
						if (array_key_exists('default', $d)) {
							$defaults[$field] = $d['default'];
						}
					}
					$this->data[$id] = $defaults;
				}			
			}
			$entity->set($this->data[$id]);
			return $entity;
		}		
	}

}

