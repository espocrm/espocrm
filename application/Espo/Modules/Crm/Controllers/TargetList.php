<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Api\Request;
use Espo\Modules\Crm\Services\TargetList as Service;
use Espo\Core\Controllers\Record;

class TargetList extends Record
{
    public function postActionUnlinkAll(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (empty($data->link)) {
            throw new BadRequest();
        }

        $this->getTargetListService()->unlinkAll($data->id, $data->link);

        return true;
    }

    public function postActionOptOut(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (empty($data->targetType)) {
            throw new BadRequest();
        }

        if (empty($data->targetId)) {
            throw new BadRequest();
        }

        $data->id = strval($data->id);
        $data->targetId = strval($data->targetId);

        $this->getTargetListService()->optOut($data->id, $data->targetType, $data->targetId);

        return true;
    }

    public function postActionCancelOptOut(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (empty($data->targetType)) {
            throw new BadRequest();
        }

        if (empty($data->targetId)) {
            throw new BadRequest();
        }

        $data->id = strval($data->id);
        $data->targetId = strval($data->targetId);

        $this->getTargetListService()->cancelOptOut($data->id, $data->targetType, $data->targetId);

        return true;
    }

    private function getTargetListService(): Service
    {
        /** @var Service */
        return $this->getRecordService();
    }
}
