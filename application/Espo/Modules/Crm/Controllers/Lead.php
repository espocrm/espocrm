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

namespace Espo\Modules\Crm\Controllers;

use Espo\Core\Controllers\Record;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;

use Espo\Core\Exceptions\NotFound;
use Espo\Modules\Crm\Tools\Lead\Convert\Params as ConvertParams;
use Espo\Modules\Crm\Tools\Lead\Convert\Values;
use Espo\Modules\Crm\Tools\Lead\ConvertService;
use stdClass;

class Lead extends Record
{
    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Conflict
     * @throws NotFound
     */
    public function postActionConvert(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        $id = $data->id ?? null;
        $records = $data->records ?? (object) [];

        if (!$id) {
            throw new BadRequest();
        }

        if (!$records instanceof stdClass) {
            throw new BadRequest();
        }

        $recordsPayload = Values::create();

        foreach (get_object_vars($records) as $entityType => $payload) {
            $recordsPayload = $recordsPayload->with($entityType, $payload);
        }

        $skipDuplicateCheck = $data->skipDuplicateCheck ?? false;

        $params = new ConvertParams($skipDuplicateCheck);

        $lead = $this->injectableFactory
            ->create(ConvertService::class)
            ->convert($id, $recordsPayload, $params);

        return $lead->getValueMap();
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function postActionGetConvertAttributes(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $data = $this->injectableFactory
            ->create(ConvertService::class)
            ->getValues($data->id);

        return $data->getRaw();
    }
}
