<?php

namespace Espo\Core\Controllers;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Utils\Util;

abstract class Record extends Base
{
	
	public static $defaultAction = 'list';	
	
	protected function getEntityManager()
	{
		return $this->getContainer()->get('entityManager');
	}
	
	protected function getRecordService()
	{
    	$moduleName = $this->getMetadata()->getScopeModuleName($this->name);
		if ($moduleName) {
			$className = '\\Espo\\Modules\\' . $moduleName . '\\Services\\' . Util::normilizeClassName($this->name);
		} else {
			$className = '\\Espo\\Services\\' . Util::normilizeClassName($this->name);
		}    	
    	if (!class_exists($className)) {
    		$className = '\\Espo\\Services\\Record';
    	}
    	
    	$service = $this->getService($className);
    	$service->setEntityName($this->name);
    	
    	return $service;
	}

	public function actionRead($params)
	{
		$id = $params['id'];
		$entity = $this->getRecordService()->getEntity($id);
		
		if (empty($entity)) {
			throw new NotFound();
		}

		return $entity->toArray();
	}
	
	public function actionPatch($params, $data)
	{
		return $this->actionUpdate($params, $data);
	}
	
	public function actionCreate($params, $data)
	{
		if (!$this->getAcl()->check($this->name, 'edit')) {
			throw new Forbidden();
		}

		$service = $this->getRecordService();
		
		if ($entity = $service->createEntity($data)) {
			return $entity->toArray();
		}

		throw new Error();
	}

	public function actionUpdate($params, $data)
	{
		if (!$this->getAcl()->check($this->name, 'edit')) {
			throw new Forbidden();
		}
	
		$id = $params['id'];
		
		if ($entity = $this->getRecordService()->updateEntity($id, $data)) {
			return $entity->toArray();
		}

		throw new Error();
	}

	public function actionList($params, $data, $request)
	{
		if (!$this->getAcl()->check($this->name, 'read')) {
			throw new Forbidden();
		}

		$where = $request->get('where');
		$offset = $request->get('offset');
		$maxSize = $request->get('maxSize');
		$asc = $request->get('asc') === 'true';
		$sortBy = $request->get('sortBy');
		$q = $request->get('q');

		$result = $this->getRecordService()->findEntities(array(
			'where' => $where,
			'offset' => $offset,
			'maxSize' => $maxSize,
			'asc' => $asc,
			'sortBy' => $sortBy,
			'q' => $q,
		));
		
		return array(
			'total' => $result['total'],
			'list' => $result['collection']->toArray()
		);
	}
	
	public function actionListLinked($params, $data, $request)
	{
		$id = $params['id'];
		$link = $params['link'];		

		$where = $request->get('where');
		$offset = $request->get('offset');
		$maxSize = $request->get('maxSize');
		$asc = $request->get('asc') === 'true';
		$sortBy = $request->get('sortBy');
		$q = $request->get('q');	

		$result = $this->getRecordService()->findLinkedEntities($id, $link, array(
			'where' => $where,
			'offset' => $offset,
			'maxSize' => $maxSize,
			'asc' => $asc,
			'sortBy' => $sortBy,
			'q' => $q,
		));
		
		
		return array(
			'total' => $result['total'],
			'list' => $result['collection']->toArray()
		);
	}

	public function actionDelete($params)
	{
		$id = $params['id'];

		if ($this->getRecordService()->deleteEntity($id)) {
			return true;
		}
		throw new Error();
	}
	
	public function actionExport($params, $data, $request)
	{
		// TODO move to service
		
		if (!$this->getAcl()->check($this->name, 'read')) {
			throw new Forbidden();
		}		
	
		$ids = $request->get('ids');
		$where = $request->get('where');
		
		return $this->getRecordService()->export($ids, $where);		
	}

	public function actionMassUpdate($params, $data)
	{
		if (!$this->getAcl()->check($this->name, 'edit')) {
			throw new Forbidden();
		}		

		$ids = $data['ids'];
		$where = $data['where'];
		$attributes = $data['attributes'];

		$idsUpdated = $this->getRecordService()->massUpdate($attributes, $ids, $where);

		return $idsUpdated;
	}

	public function actionMassDelete($params, $data)
	{
		if (!$this->getAcl()->check($this->name, 'delete')) {
			throw new Forbidden();
		}

		$ids = $data['ids'];
		$where = $data['where'];

		$idsDeleted = $this->getRecordService()->massDelete($ids, $where);

		return $idsDeleted;
	}

	public function actionCreateLink($params, $data)
	{
		$id = $params['id'];
		$link = $params['link'];
		
		$foreignIds = array();		
		if (isset($data['id'])) {
			$foreignIds[] = $data['id'];
		}		
		if (isset($data['ids']) && is_array($data['ids'])) {
			foreach ($data['ids'] as $foreignId) {
				$foreignIds[] = $foreignId;
			}
		}		
		
		$result = false;
		foreach ($foreignIds as $foreignId) {
			if ($this->getRecordService()->linkEntity($id, $link, $foreignId)) {
				$result = $result || true;
			}
			if ($result) {
				return true;
			}
		}	
		
		throw new Error();		
	}
	
	public function actionRemoveLink($params, $data)
	{
		$id = $params['id'];
		$link = $params['link'];
		
		$foreignIds = array();		
		if (isset($data['id'])) {
			$foreignIds[] = $data['id'];
		}		
		if (isset($data['ids']) && is_array($data['ids'])) {
			foreach ($data['ids'] as $foreignId) {
				$foreignIds[] = $foreignId;
			}
		}
		
		$result = false;
		foreach ($foreignIds as $foreignId) {
			if ($this->getRecordService()->unlinkEntity($id, $link, $foreignId)) {
				$result = $result || true;
			}
			if ($result) {
				return true;
			}
		}	
		
		throw new Error();		
	}
	
	public function actionFollow($params)
	{
		if (!$this->getAcl()->check($this->name, 'read')) {
			throw new Forbidden();
		}
		$id = $params['id'];
		return $this->getRecordService()->follow($id);		
	}
	
	public function actionUnfollow($params)
	{
		if (!$this->getAcl()->check($this->name, 'read')) {
			throw new Forbidden();
		}
		$id = $params['id'];
		return $this->getRecordService()->unfollow($id);		
	}
}

