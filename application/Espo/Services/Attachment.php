<?php

namespace Espo\Services;

class Attachment extends Record
{
	protected function init()
	{
		$this->dependencies[] = 'fileManager';
	}
	
	protected function getFileManager()
	{
		return $this->getInjection('fileManager');
	}

	public function createEntity($data)
	{
		$entity = parent::createEntity($data);
		
		list($prefix, $contents) = explode(',', $data['file']);		
		
		if (!empty($entity->id)) {
			$this->getFileManager()->setContent(base64_decode($contents), 'data/upload/' . $entity->id);
		}
					
		return $entity;
	}
}

