<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Controllers;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\NotFoundSilent;
use Espo\Core\Select\SearchParams;
use Espo\Core\Utils\Json;

use stdClass;

class Record extends RecordBase
{
    /**
     * List related records.
     *
     * @throws BadRequest
     * @throws NotFound
     * @throws Forbidden
     * @noinspection PhpUnused
     */
    public function getActionListLinked(Request $request): stdClass
    {
        $id = $request->getRouteParam('id');
        $link = $request->getRouteParam('link');

        if (!$id) {
            throw new BadRequest("No ID.");
        }

        if (!$link) {
            throw new BadRequest("No link.");
        }

        $searchParams = $this->fetchSearchParamsFromRequest($request);

        $result = $this->getRecordService()->findLinked($id, $link, $searchParams);

        return $result->toApiOutput();
    }

    /**
     * Relate records.
     *
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
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
     * Un-relate records.
     *
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
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
     *
     * @throws BadRequest
     * @throws NotFoundSilent
     * @throws Forbidden
     * @noinspection PhpUnused
     */
    public function putActionFollow(Request $request): bool
    {
        $id = $request->getRouteParam('id');

        if (!$id) {
            throw new BadRequest("No ID.");
        }

        $this->getRecordService()->follow($id);

        return true;
    }

    /**
     * Unfollow a record.
     *
     * @throws NotFoundSilent
     * @throws BadRequest
     * @noinspection PhpUnused
     */
    public function deleteActionUnfollow(Request $request): bool
    {
        $id = $request->getRouteParam('id');

        if (!$id) {
            throw new BadRequest("No ID.");
        }

        $this->getRecordService()->unfollow($id);

        return true;
    }

    /**
     * @throws BadRequest
     */
    private function fetchMassLinkSearchParamsFromRequest(Request $request): SearchParams
    {
        $data = $request->getParsedBody();

        $where = $data->where ?? null;

        if ($where !== null) {
            $where = json_decode(Json::encode($where), true);
        }

        $params = json_decode(
            Json::encode(
                $data->searchParams ?? $data->selectData ?? (object) []
            ),
            true
        );

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
