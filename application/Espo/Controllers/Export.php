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

namespace Espo\Controllers;

use Espo\Core\{
    Api\Request,
    Exceptions\BadRequest,
};

use Espo\Tools\Export\{
    Service,
    Params,
};

use StdClass;

class Export
{
    private $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function postActionProcess(Request $request): StdClass
    {
        $params = $this->fetchRawParamsFromRequest($request);

        $result = $this->service->process($params);

        return (object) [
            'id' => $result->getAttachmentId(),
        ];
    }

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
                $params['where'] = json_decode(json_encode($where), true);
            }

            if (!is_null($searchParams)) {
                $params['searchParams'] = json_decode(json_encode($searchParams), true);
            }
        }
        else if (!is_null($ids)) {
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

        return Params::fromRaw($params);
    }
}
