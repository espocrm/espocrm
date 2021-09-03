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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Api\Request;
use Espo\Core\Select\SearchParams;

use stdClass;

class Record extends RecordBase
{
    /**
     * List related records.
     */
    public function getActionListLinked(Request $request): stdClass
    {
        $id = $request->getRouteParam('id');
        $link = $request->getRouteParam('link');

        $searchParams = $this->fetchSearchParamsFromRequest($request);

        $recordCollection = $this->getRecordService()->findLinked($id, $link, $searchParams);

        return (object) [
            'total' => $recordCollection->getTotal(),
            'list' => $recordCollection->getValueMapList(),
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
            $searchParams = $this->fetchMassLinkSearchParamsFromRequest($request);

            return $this->getRecordService()->massLink($id, $link, $searchParams);
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

        $this->getRecordService()->follow($id);

        return true;
    }

    /**
     * Unfollow a record.
     */
    public function deleteActionUnfollow(Request $request): bool
    {
        $id = $request->getRouteParam('id');

        $this->getRecordService()->unfollow($id);

        return true;
    }

    private function fetchMassLinkSearchParamsFromRequest(Request $request): SearchParams
    {
        $data = $request->getParsedBody();

        $where = $data->where ?? null;

        if ($where !== null) {
            $where = json_decode(json_encode($where), true);
        }

        $params = json_decode(json_encode(
            $data->searchParams ?? $data->selectData ?? (object) []
        ), true);

        if ($where !== null && !is_array($where)) {
            throw new BadRequest("Bad 'where.");
        }

        if ($where !== null) {
            $params['where'] = array_merge(
                $params['where'] ?? [],
                $where
            );
        }

        unset($params['select']);

        return SearchParams::fromRaw($params);
    }
}
