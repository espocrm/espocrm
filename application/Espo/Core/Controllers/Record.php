<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Controllers;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Utils\Util;

class Record extends Base
{
    const MAX_SIZE_LIMIT = 200;

    public static $defaultAction = 'list';

    protected $defaultRecordServiceName = 'Record';

    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    protected function getRecordService($name = null)
    {
        if (empty($name)) {
            $name = $this->name;
        }

        if ($this->getServiceFactory()->checkExists($name)) {
            $service = $this->getServiceFactory()->create($name);
        } else {
            $service = $this->getServiceFactory()->create($this->defaultRecordServiceName);
            $service->setEntityType($name);
        }

        return $service;
    }

    public function actionRead($params, $data, $request)
    {
        $id = $params['id'];
        $entity = $this->getRecordService()->read($id);

        if (!$entity) throw new NotFound();

        return $entity->getValueMap();
    }

    public function actionPatch($params, $data, $request)
    {
        return $this->actionUpdate($params, $data, $request);
    }

    public function actionCreate($params, $data, $request)
    {
        if (!is_object($data)) throw new BadRequest();

        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'create')) {
            throw new Forbidden();
        }

        $service = $this->getRecordService();

        if ($entity = $service->create($data)) {
            return $entity->getValueMap();
        }

        throw new Error();
    }

    public function actionUpdate($params, $data, $request)
    {
        if (!is_object($data)) throw new BadRequest();

        if (!$request->isPut() && !$request->isPatch()) {
            throw new BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'edit')) {
            throw new Forbidden();
        }

        $id = $params['id'];

        if ($entity = $this->getRecordService()->update($id, $data)) {
            return $entity->getValueMap();
        }

        throw new Error();
    }

    public function actionList($params, $data, $request)
    {
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Forbidden();
        }

        $params = [];
        $this->fetchListParamsFromRequest($params, $request, $data);

        $maxSizeLimit = $this->getConfig()->get('recordListMaxSizeLimit', self::MAX_SIZE_LIMIT);
        if (empty($params['maxSize'])) {
            $params['maxSize'] = $maxSizeLimit;
        }
        if (!empty($params['maxSize']) && $params['maxSize'] > $maxSizeLimit) {
            throw new Forbidden("Max size should should not exceed " . $maxSizeLimit . ". Use offset and limit.");
        }

        $result = $this->getRecordService()->find($params);

        if (is_array($result)) {
            return [
                'total' => $result['total'],
                'list' => isset($result['collection']) ? $result['collection']->getValueMapList() : $result['list']
            ];
        }

        return [
            'total' => $result->total,
            'list' => isset($result->collection) ? $result->collection->getValueMapList() : $result->list
        ];
    }

    public function getActionListKanban($params, $data, $request)
    {
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Forbidden();
        }

        $params = [];
        $this->fetchListParamsFromRequest($params, $request, $data);

        $maxSizeLimit = $this->getConfig()->get('recordListMaxSizeLimit', self::MAX_SIZE_LIMIT);
        if (empty($params['maxSize'])) {
            $params['maxSize'] = $maxSizeLimit;
        }
        if (!empty($params['maxSize']) && $params['maxSize'] > $maxSizeLimit) {
            throw new Forbidden("Max size should should not exceed " . $maxSizeLimit . ". Use offset and limit.");
        }

        $result = $this->getRecordService()->getListKanban($params);

        return (object) [
            'total' => $result->total,
            'list' => $result->collection->getValueMapList(),
            'additionalData' => $result->additionalData
        ];
    }

    protected function fetchListParamsFromRequest(&$params, $request, $data)
    {
        \Espo\Core\Utils\ControllerUtil::fetchListParamsFromRequest($params, $request, $data);
    }

    public function actionListLinked($params, $data, $request)
    {
        $id = $params['id'];
        $link = $params['link'];

        $params = [];
        $this->fetchListParamsFromRequest($params, $request, $data);

        $maxSizeLimit = $this->getConfig()->get('recordListMaxSizeLimit', self::MAX_SIZE_LIMIT);
        if (empty($params['maxSize'])) {
            $params['maxSize'] = $maxSizeLimit;
        }
        if (!empty($params['maxSize']) && $params['maxSize'] > $maxSizeLimit) {
            throw new Forbidden("Max size should should not exceed " . $maxSizeLimit . ". Use offset and limit.");
        }

        $result = $this->getRecordService()->findLinked($id, $link, $params);

        if (is_array($result)) {
            return [
                'total' => $result['total'],
                'list' => isset($result['collection']) ? $result['collection']->getValueMapList() : $result['list']
            ];
        }

        return (object) [
            'total' => $result->total,
            'list' => isset($result->collection) ? $result->collection->getValueMapList() : $result->list
        ];
    }

    public function actionDelete($params, $data, $request)
    {
        if (!$request->isDelete()) {
            throw new BadRequest();
        }

        $id = $params['id'];

        if ($this->getRecordService()->delete($id)) {
            return true;
        }
        throw new Error();
    }

    public function actionExport($params, $data, $request)
    {
        if (!is_object($data)) throw new BadRequest();

        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if ($this->getConfig()->get('exportDisabled') && !$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        if ($this->getAcl()->get('exportPermission') !== 'yes' && !$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Forbidden();
        }

        $ids = isset($data->ids) ? $data->ids : null;
        $where = isset($data->where) ? json_decode(json_encode($data->where), true) : null;
        $byWhere = isset($data->byWhere) ? $data->byWhere : false;
        $selectData = isset($data->selectData) ? json_decode(json_encode($data->selectData), true) : null;

        $params = array();
        if ($byWhere) {
            $params['selectData'] = $selectData;
            $params['where'] = $where;
        } else {
            $params['ids'] = $ids;
        }

        if (isset($data->attributeList)) {
            $params['attributeList'] = $data->attributeList;
        }

        if (isset($data->fieldList)) {
            $params['fieldList'] = $data->fieldList;
        }

        if (isset($data->format)) {
            $params['format'] = $data->format;
        }

        return [
            'id' => $this->getRecordService()->export($params)
        ];
    }

    public function actionMassUpdate($params, $data, $request)
    {
        if (!$request->isPut()) {
            throw new BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'edit')) {
            throw new Forbidden();
        }
        if (empty($data->attributes)) {
            throw new BadRequest();
        }

        if ($this->getAcl()->get('massUpdatePermission') !== 'yes') {
            throw new Forbidden();
        }

        $params = array();
        if (property_exists($data, 'where') && !empty($data->byWhere)) {
            $params['where'] = json_decode(json_encode($data->where), true);
            if (property_exists($data, 'selectData')) {
                $params['selectData'] = json_decode(json_encode($data->selectData), true);
            }
        } else if (property_exists($data, 'ids')) {
            $params['ids'] = $data->ids;
        }

        $attributes = $data->attributes;

        $idsUpdated = $this->getRecordService()->massUpdate($params, $attributes);

        return $idsUpdated;
    }

    public function postActionMassDelete($params, $data, $request)
    {
        if (!$this->getAcl()->check($this->name, 'delete')) {
            throw new Forbidden();
        }

        $actionParams = $this->getMassActionParamsFromData($data);

        if (array_key_exists('where', $actionParams)) {
            if ($this->getAcl()->get('massUpdatePermission') !== 'yes') {
                throw new Forbidden();
            }
        }

        return $this->getRecordService()->massDelete($actionParams);
    }

    public function actionCreateLink($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (empty($params['id']) || empty($params['link'])) {
            throw new BadRequest();
        }

        $id = $params['id'];
        $link = $params['link'];

        if (!empty($data->massRelate)) {
            if (!is_array($data->where)) {
                throw new BadRequest();
            }
            $where = json_decode(json_encode($data->where), true);

            $selectData = null;
            if (isset($data->selectData) && is_array($data->selectData)) {
                $selectData = json_decode(json_encode($data->selectData), true);
            }

            return $this->getRecordService()->massLink($id, $link, $where, $selectData);
        } else {
            $foreignIdList = array();
            if (isset($data->id)) {
                $foreignIdList[] = $data->id;
            }
            if (isset($data->ids) && is_array($data->ids)) {
                foreach ($data->ids as $foreignId) {
                    $foreignIdList[] = $foreignId;
                }
            }

            $result = false;
            foreach ($foreignIdList as $foreignId) {
                if ($this->getRecordService()->link($id, $link, $foreignId)) {
                    $result = true;
                }
            }
            if ($result) {
                return true;
            }
        }

        throw new Error();
    }

    public function actionRemoveLink($params, $data, $request)
    {
        if (!$request->isDelete()) {
            throw new BadRequest();
        }

        $id = $params['id'];
        $link = $params['link'];

        if (empty($params['id']) || empty($params['link'])) {
            throw new BadRequest();
        }

        $foreignIdList = [];
        if (isset($data->id)) {
            $foreignIdList[] = $data->id;
        }
        if (isset($data->ids) && is_array($data->ids)) {
            foreach ($data->ids as $foreignId) {
                $foreignIdList[] = $foreignId;
            }
        }

        $result = false;
        foreach ($foreignIdList as $foreignId) {
            if ($this->getRecordService()->unlink($id, $link, $foreignId)) {
                $result = $result || true;
            }
        }
        if ($result) {
            return true;
        }

        throw new Error();
    }

    public function actionFollow($params, $data, $request)
    {
        if (!$request->isPut()) {
            throw new BadRequest();
        }
        if (!$this->getAcl()->check($this->name, 'stream')) {
            throw new Forbidden();
        }
        $id = $params['id'];
        return $this->getRecordService()->follow($id);
    }

    public function actionUnfollow($params, $data, $request)
    {
        if (!$request->isDelete()) {
            throw new BadRequest();
        }
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Forbidden();
        }
        $id = $params['id'];
        return $this->getRecordService()->unfollow($id);
    }

    public function actionMerge($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (empty($data->targetId) || empty($data->sourceIds) || !is_array($data->sourceIds) || !($data->attributes instanceof \StdClass)) {
            throw new BadRequest();
        }
        $targetId = $data->targetId;
        $sourceIds = $data->sourceIds;
        $attributes = $data->attributes;

        if (!$this->getAcl()->check($this->name, 'edit')) {
            throw new Forbidden();
        }

        return $this->getRecordService()->merge($targetId, $sourceIds, $attributes);
    }

    public function postActionGetDuplicateAttributes($params, $data, $request)
    {
        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'create')) {
            throw new Forbidden();
        }
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Forbidden();
        }

        return $this->getRecordService()->getDuplicateAttributes($data->id);
    }

    public function postActionMassFollow($params, $data, $request)
    {
        if (!$this->getAcl()->check($this->name, 'stream')) {
            throw new Forbidden();
        }

        if (property_exists($data, 'ids')) {
            $params['ids'] = $data->ids;
        }

        return $this->getRecordService()->massFollow($params);
    }

    public function postActionMassUnfollow($params, $data, $request)
    {
        if (!$this->getAcl()->check($this->name, 'stream')) {
            throw new Forbidden();
        }

        if (property_exists($data, 'ids')) {
            $params['ids'] = $data->ids;
        }

        return $this->getRecordService()->massUnfollow($params);
    }

    protected function getMassActionParamsFromData($data)
    {
        $params = [];
        if (property_exists($data, 'where') && !empty($data->byWhere)) {
            $where = json_decode(json_encode($data->where), true);
            $params['where'] = $where;
            if (property_exists($data, 'selectData')) {
                $params['selectData'] = json_decode(json_encode($data->selectData), true);
            }
        }
        if (property_exists($data, 'ids')) {
            $params['ids'] = $data->ids;
        }

        return $params;
    }

    public function postActionMassRecalculateFormula($params, $data, $request)
    {
        if (!$this->getUser()->isAdmin()) throw new Forbidden();
        if (!$this->getAcl()->check($this->name, 'edit')) throw new Forbidden();

        return $this->getRecordService()->massRecalculateFormula($this->getMassActionParamsFromData($data));
    }

    public function postActionRestoreDeleted($params, $data, $request)
    {
        if (!$this->getUser()->isAdmin()) throw new Forbidden();

        $id = $data->id ?? null;
        if (!$id) throw new Forbidden();

        return $this->getRecordService()->restoreDeleted($id);
    }
}
