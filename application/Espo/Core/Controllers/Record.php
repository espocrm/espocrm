<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\{
    Forbidden,
    BadRequest,
};

use Espo\Core\{
    Record\Collection as RecordCollection,
    Api\Request,
};

use StdClass;

class Record extends RecordBase
{
    /**
     * Kanban data.
     */
    public function getActionListKanban(Request $request): StdClass
    {
        $data = $request->getParsedBody();

        $listParams = [];

        $this->fetchListParamsFromRequest($listParams, $request, $data);

        $maxSizeLimit = $this->config->get('recordListMaxSizeLimit', self::MAX_SIZE_LIMIT);

        if (empty($listParams['maxSize'])) {
            $listParams['maxSize'] = $maxSizeLimit;
        }

        if (!empty($listParams['maxSize']) && $listParams['maxSize'] > $maxSizeLimit) {
            throw new Forbidden(
                "Max size should should not exceed " . $maxSizeLimit . ". Use offset and limit."
            );
        }

        $result = $this->getRecordService()->getListKanban($listParams);

        return (object) [
            'total' => $result->getTotal(),
            'list' => $result->getCollection()->getValueMapList(),
            'additionalData' => $result->getData(),
        ];
    }

    /**
     * List related records.
     */
    public function getActionListLinked(Request $request): StdClass
    {
        $id = $request->getRouteParam('id');
        $link = $request->getRouteParam('link');

        $data = $request->getParsedBody();

        $listParams = [];

        $this->fetchListParamsFromRequest($listParams, $request, $data);

        $maxSizeLimit = $this->config->get('recordListMaxSizeLimit', self::MAX_SIZE_LIMIT);

        if (empty($listParams['maxSize'])) {
            $listParams['maxSize'] = $maxSizeLimit;
        }

        if (!empty($listParams['maxSize']) && $listParams['maxSize'] > $maxSizeLimit) {
            throw new Forbidden(
                "Max size should should not exceed " . $maxSizeLimit . ". Use offset and limit."
            );
        }

        $result = $this->getRecordService()->findLinked($id, $link, $listParams);

        if ($result instanceof RecordCollection) {
            return (object) [
                'total' => $result->getTotal(),
                'list' => $result->getValueMapList(),
            ];
        }

        if (is_array($result)) {
            return [
                'total' => $result['total'],
                'list' => isset($result['collection']) ?
                    $result['collection']->getValueMapList() :
                    $result['list']
            ];
        }

        return (object) [
            'total' => $result->total,
            'list' => isset($result->collection) ?
                $result->collection->getValueMapList() :
                $result->list
        ];
    }

    /**
     * Relate records.
     */
    public function postActionCreateLink(Request $request): bool
    {
        $id = $request->getRouteParam('id');
        $link = $request->getRouteParam('link');

        $data = $request->getParsedBody();

        if (!$id || !$link) {
            throw new BadRequest();
        }

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
            $this->getRecordService()->link($id, $link, $foreignId);

            $result = true;
        }

        return $result;
    }

    /**
     * Unrelate records.
     */
    public function deleteActionRemoveLink(Request $request): bool
    {
        $id = $request->getRouteParam('id');
        $link = $request->getRouteParam('link');

        $data = $request->getParsedBody();

        if (!$id || !$link) {
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
            $this->getRecordService()->unlink($id, $link, $foreignId);

            $result = true;
        }

        return $result;
    }

    /**
     * Follow a record.
     */
    public function putActionFollow(Request $request): bool
    {
        $id = $request->getRouteParam('id');

        return $this->getRecordService()->follow($id);
    }

    /**
     * Unfollow a record.
     */
    public function deleteActionUnfollow(Request $request): bool
    {
        $id = $request->getRouteParam('id');

        return $this->getRecordService()->unfollow($id);
    }

    public function postActionMerge(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (
            empty($data->targetId) ||
            empty($data->sourceIds) ||
            !is_array($data->sourceIds) ||
            !($data->attributes instanceof StdClass)
        ) {
            throw new BadRequest();
        }

        $targetId = $data->targetId;
        $sourceIds = $data->sourceIds;
        $attributes = $data->attributes;

        if (!$this->acl->check($this->getEntityType(), 'edit')) {
            throw new Forbidden("No edit access for {$this->name}.");
        }

        $this->getRecordService()->merge($targetId, $sourceIds, $attributes);

        return true;
    }
}
