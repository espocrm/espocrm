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
    Exceptions\BadRequest,
    MassAction\Service,
    MassAction\Result,
    MassAction\Params,
    Api\Request,
};

use stdClass;
use RuntimeException;

/**
 * Mass-Action framework.
 */
class MassAction
{
    private $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function postActionProcess(Request $request): stdClass
    {
        $body = $request->getParsedBody();

        $entityType = $body->entityType ?? null;
        $action = $body->action ?? null;
        $params = $body->params ?? null;
        $data = $body->data ?? (object) [];

        if (!$entityType || !$action || !$params) {
            throw new BadRequest();
        }

        $rawParams = $this->prepareMassActionParams($params);

        try {
            $massActionParams = Params::fromRaw($rawParams, $entityType);
        }
        catch (RuntimeException $e) {
            throw new BadRequest($e->getMessage());
        }

        $result = $this->service->process(
            $entityType,
            $action,
            $massActionParams,
            $data
        );

        return $this->convertResult($result);
    }

    private function prepareMassActionParams(stdClass $data): array
    {
        $where = $data->where ?? null;
        $searchParams = $data->searchParams ?? $data->selectData ?? null;
        $ids = $data->ids ?? null;

        if (!is_null($where) || !is_null($searchParams)) {
            $params = [];

            if (!is_null($where)) {
                $params['where'] = json_decode(json_encode($where), true);
            }

            if (!is_null($searchParams)) {
                $params['searchParams'] = json_decode(json_encode($searchParams), true);
            }

            return $params;
        }

        if (!is_null($ids)) {
            return [
                'ids' => $ids,
            ];
        }

        throw new BadRequest("Bad search params for mass action.");
    }

    private function convertResult(Result $result): stdClass
    {
        $data = (object) [];

        if ($result->hasCount()) {
            $data->count = $result->getCount();
        }

        if ($result->hasIds()) {
            $data->ids = $result->getIds();
        }

        return $data;
    }
}
