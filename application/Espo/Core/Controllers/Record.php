<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/
namespace Espo\Core\Controllers;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Slim\Http\Request;

class Record extends
    Base
{

    const MAX_SIZE_LIMIT = 200;

    public static $defaultAction = 'list';

    public function actionRead($params)
    {
        /**
         * @var Entity $entity
         */
        $id = $params['id'];
        $entity = $this->getRecordService()->getEntity($id);
        if (empty($entity)) {
            throw new NotFound();
        }
        return $entity->toArray();
    }

    protected function getRecordService($name = null)
    {
        /**
         * @var \Espo\Services\Record $service
         */
        if (empty($name)) {
            $name = $this->name;
        }
        if ($this->getServiceFactory()->checkExists($name)) {
            $service = $this->getServiceFactory()->create($name);
        } else {
            $service = $this->getServiceFactory()->create('Record');
            $service->setEntityName($name);
        }
        return $service;
    }

    public function actionPatch($params, $data)
    {
        return $this->actionUpdate($params, $data);
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

    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @since 1.0
     * @throws Forbidden
     */
    public function actionList($params, $data, $request)
    {
        /**
         * @var EntityCollection $collection
         */
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Forbidden();
        }
        $where = $request->get('where');
        $offset = $request->get('offset');
        $maxSize = $request->get('maxSize');
        $asc = $request->get('asc') === 'true';
        $sortBy = $request->get('sortBy');
        $q = $request->get('q');
        if (empty($maxSize)) {
            $maxSize = self::MAX_SIZE_LIMIT;
        }
        if (!empty($maxSize) && $maxSize > self::MAX_SIZE_LIMIT) {
            throw new Forbidden();
        }
        $result = $this->getRecordService()->findEntities(array(
            'where' => $where,
            'offset' => $offset,
            'maxSize' => $maxSize,
            'asc' => $asc,
            'sortBy' => $sortBy,
            'q' => $q,
        ));
        $collection = $result['collection'];
        return array(
            'total' => $result['total'],
            'list' => $collection->toArray()
        );
    }

    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @since 1.0
     * @throws Forbidden
     */
    public function actionListLinked($params, $data, $request)
    {
        /**
         * @var EntityCollection $collection
         */
        $id = $params['id'];
        $link = $params['link'];
        $where = $request->get('where');
        $offset = $request->get('offset');
        $maxSize = $request->get('maxSize');
        $asc = $request->get('asc') === 'true';
        $sortBy = $request->get('sortBy');
        $q = $request->get('q');
        if (empty($maxSize)) {
            $maxSize = self::MAX_SIZE_LIMIT;
        }
        if (!empty($maxSize) && $maxSize > self::MAX_SIZE_LIMIT) {
            throw new Forbidden();
        }
        $result = $this->getRecordService()->findLinkedEntities($id, $link, array(
            'where' => $where,
            'offset' => $offset,
            'maxSize' => $maxSize,
            'asc' => $asc,
            'sortBy' => $sortBy,
            'q' => $q,
        ));
        $collection = $result['collection'];
        return array(
            'total' => $result['total'],
            'list' => $collection->toArray()
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

    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @since 1.0
     * @throws Error
     * @throws Forbidden
     */
    public function actionExport($params, $data, $request)
    {
        if ($this->getConfig()->get('disableExport') && !$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Forbidden();
        }
        $ids = $request->get('ids');
        $where = $request->get('where');
        return array(
            'id' => $this->getRecordService()->export($ids, $where)
        );
    }

    public function actionMassUpdate($params, $data, $request)
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

    public function actionMassDelete($params, $data, $request)
    {
        if (!$this->getAcl()->check($this->name, 'delete')) {
            throw new Forbidden();
        }
        $ids = $data['ids'];
        $where = $data['where'];
        $idsRemoved = $this->getRecordService()->massRemove($ids, $where);
        return $idsRemoved;
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
        }
        if ($result) {
            return true;
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
        }
        if ($result) {
            return true;
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

    /**
     * @return EntityManager
     * @since 1.0
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }
}

