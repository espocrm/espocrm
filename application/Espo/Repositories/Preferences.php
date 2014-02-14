<?php

namespace Espo\Repositories;

use Espo\ORM\Entity;

class Preferences extends \Espo\Core\ORM\Repository
{
	protected $dependencies = array(
		'fileManager',
		'metadata',
	);
	
	protected $data = array();
	
	protected $entityName = 'Preferences';
	
	protected function getFileManager()
	{
		return $this->getInjection('fileManager');
	}
	
	protected function getMetadata()
	{
		return $this->getInjection('metadata');
	}
	
	protected function getFilePath($id)
	{
		return 'data/preferences/' . $id . '.json';
	}
	
	public function get($id = null)
	{				
		if ($id) {
			$entity = $this->entityFactory->create('Preferences');
			$entity->id = $id;
			if (empty($this->data[$id])) {
				$fileName = $this->getFilePath($id);
				
				if (file_exists($fileName)) {
					$this->data[$id] = json_decode($this->getFileManager()->getContents($fileName), true);
				} else {
					$fields = $this->getMetadata()->get('entityDefs.Preferences.fields');
					$defaults = array();
					$defaults['dashboardLayout'] = $this->getMetadata()->get('app.defaultDashboardLayout');
					foreach ($fields as $field => $d) {
						if (array_key_exists('default', $d)) {
							$defaults[$field] = $d['default'];							
						}						
					}
					$this->data[$id] = $defaults;
				}			
			}
			
			$entity->set($this->data[$id]);
			$d = $entity->toArray();
			return $entity;
		}		
	}
	
	public function save(Entity $entity)
	{
		if ($entity->id) {
			$this->data[$entity->id] = $entity->toArray();
			
			$fileName = $this->getFilePath($entity->id);
			$this->getFileManager()->putContents($fileName, json_encode($this->data[$entity->id]));
			return $entity;
		}
	}
		
	public function remove(Entity $entity)
	{
		$fileName = $this->getFilePath($id);
		unlink($fileName);
		if (!file_exists($fileName)) {
			return true;
		}
	}

	public function find(array $params)
	{
	}
	
	public function findOne(array $params)
	{
	}

	public function getAll()
	{
	}
	
	public function count(array $params)
	{
	}
}

