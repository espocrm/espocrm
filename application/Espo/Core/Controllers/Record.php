<?php

namespace Espo\Core\Controllers;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

abstract class Record extends Base
{
	protected $serviceClassName = '\\Espo\\Services\\Record';
	
	public $defaultAction = 'list';
	
	protected function loadService()
	{
		parent::loadService();
		$this->service->setEntityName($this->name);
	}

	public function actionRead($params)
	{
		$id = $params['id'];
		$service = $this->getService();
		$entity = $service->getEntity($id);

		if (!$this->getAcl()->check($entity, 'read')) {
			throw new Forbidden();
		}

		return $entity;
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

		$service = $this->getService();
		
		if ($entity = $service->createEntity($data)) {
			return $entity;
		}

		throw new Error();
	}

	public function actionUpdate($params, $data)
	{
		if (!$this->getAcl()->check($this->name, 'edit')) {
			throw new Forbidden();
		}
	
		$id = $params['id'];
		
		if ($this->getService()->updateEntity($id, $data)) {
			return $entity;
		}

		throw new Error();
	}

	public function actionList($params, $where)
	{
		if (!$this->getAcl()->check($this->name, 'read')) {
			throw new Forbidden();
		}

		$where = $data['where'];
		$offset = $data['offset'];
		$limit = $data['limit'];
		$asc = $data['asc'];
		$sortBy = $data['sortBy'];

		$entityList = $this->getService()->findEntities(array(
			'where' => $where,
			'offset' => $offset,
			'limit' => $limit,
			'asc' => $asc,
			'sortBy' => $sortBy,
		));
		
		return array(
			'total' => count($entityList),
			'list' => $entityList
		);
	}

	public function actionDelete($params)
	{
		$id = $params['id'];

		if ($this->getService()->deleteEntity($id)) {
			return true;
		}
		throw new Error();
	}

	public function actionMassUpdate($params, $data)
	{
		if (!$this->getAcl()->check($this->name, 'edit')) {
			throw new Forbidden();
		}

		$ids = $data['ids'];
		$where = $data['where'];

		$idsUpdated = $this->getService()->massUpdate($ids, $where);

		return $idsUpdated;
	}

	public function actionMassDelete($params, $data)
	{
		if (!$this->getAcl()->check($this->name, 'delete')) {
			throw new Forbidden();
		}

		$ids = $data['ids'];
		$where = $data['where'];

		$idsDeleted = $this->getService()->massDelete($ids, $where);

		return $idsDeleted;
	}

	public function actionListLinked($params, $data)
	{
		$id = $params['id'];
		$link = $params['link'];

		$where = $data['where'];
		$offset = $data['offset'];
		$limit = $data['limit'];
		$asc = $data['asc'];
		$sortBy = $data['sortBy'];

		$entityList = $this->getService()->findLinkedEntities($id, $link, array(
			'where' => $where,
			'offset' => $offset,
			'limit' => $limit,
			'asc' => $asc,
			'sortBy' => $sortBy,
		));

		return array(
			'total' => count($entityList),
			'list' => $entityList
		);
	}

	public function actionCreateLink($params)
	{
		$id = $params['id'];
		$link = $params['link'];
		$foreignId = $params['foreignId'];

		if ($this->getService()->linkEntity($id, $link, $foreignId)) {
			return true;
		}

		throw new Error();
	}
	
	public function actionRemoveLink($params)
	{
		$id = $params['id'];
		$link = $params['link'];
		$foreignId = $params['foreignId'];

		if ($this->getService()->unlinkEntity($id, $link, $foreignId)) {
			return true;
		}

		throw new Error();
	}
}
