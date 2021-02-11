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
    RecordServiceContainer,
    Api\Request,
};

use StdClass;

class MassAction
{
    protected $recordServiceContainer;

    public function __construct(RecordServiceContainer $recordServiceContainer)
    {
        $this->recordServiceContainer = $recordServiceContainer;
    }

    public function postActionProcess(Request $request) : StdClass
    {
        $body = $request->getParsedBody();

        $entityType = $body->entityType ?? null;
        $action = $body->action ?? null;
        $params = $body->params ?? null;
        $data = $body->data ?? (object) [];

        if (!$entityType || !$action || !$params) {
            throw new BadRequest();
        }

        $service = $this->recordServiceContainer->get($entityType);

        $result = $service->massAction(
            $action,
            $this->prepareMassActionParams($params),
            $data
        );

        return $result->getValueMap();
    }

    protected function prepareMassActionParams(StdClass $data) : array
    {
        $params = [];

        $where = $data->where ?? null;
        $searchParams = $data->searchParams ?? null;
        $ids = $data->ids ?? null;

        if (!is_null($where) || !is_null($searchParams)) {
            $params = [];

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
        else {
            throw new BadRequest("Bad search params for mass action.");
        }

        return $params;
    }
}
