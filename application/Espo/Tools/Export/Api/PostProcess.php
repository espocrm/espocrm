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

namespace Espo\Tools\Export\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Utils\Json;
use Espo\Tools\Export\Params;
use Espo\Tools\Export\Service;
use Espo\Tools\Export\ServiceParams;

use stdClass;

class PostProcess implements Action
{
    public function __construct(private Service $service)
    {}

    public function process(Request $request): Response
    {
        $params = $this->fetchRawParamsFromRequest($request);

        $serviceParams = ServiceParams::create()
            ->withIsIdle($request->getParsedBody()->idle ?? false);

        $result = $this->service->process($params, $serviceParams);

        if ($result->hasResult()) {
            $subResult = $result->getResult();

            assert($subResult !== null);

            return ResponseComposer::json([
                'id' => $subResult->getAttachmentId()
            ]);
        }

        return ResponseComposer::json([
            'exportId' => $result->getId()
        ]);
    }

    /**
     * @throws BadRequest
     */
    private function fetchRawParamsFromRequest(Request $request): Params
    {
        $data = $request->getParsedBody();

        $entityType = $data->entityType ?? null;

        if (!$entityType) {
            throw new BadRequest("No entityType.");
        }

        $params['entityType'] = $entityType;

        $where = $data->where ?? null;
        $searchParams = $data->searchParams ?? $data->selectData ?? null;
        $ids = $data->ids ?? null;

        if (!is_null($where) || !is_null($searchParams)) {
            if (!is_null($where)) {
                $params['where'] = json_decode(Json::encode($where), true);
            }

            if (!is_null($searchParams)) {
                $params['searchParams'] = json_decode(Json::encode($searchParams), true);
            }
        } else if (!is_null($ids)) {
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

        $obj = Params::fromRaw($params);

        if (isset($data->params) && $data->params instanceof stdClass) {
            foreach (get_object_vars($data->params) as $key => $value) {
                $obj = $obj->withParam($key, $value);
            }
        }

        return $obj;
    }
}
