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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Controllers\Record;
use Espo\Modules\Crm\Tools\TargetList\OptOutService;
use Espo\Modules\Crm\Tools\TargetList\RecordService;

class TargetList extends Record
{
    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function postActionUnlinkAll(Request $request): bool
    {
        $data = $request->getParsedBody();

        $id = $data->id ?? null;
        $link = $data->link ?? null;

        if (
            !is_string($id) ||
            !is_string($link)
        ) {
            throw new BadRequest();
        }

        $this->injectableFactory->create(RecordService::class)->unlinkAll($id, $link);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws NotFound
     * @throws Forbidden
     */
    public function postActionOptOut(Request $request): bool
    {
        $data = $request->getParsedBody();

        $id = $data->id ?? null;
        $targetType = $data->targetType ?? null;
        $targetId = $data->targetId ?? null;

        if (
            !is_string($id) ||
            !is_string($targetType) ||
            !is_string($targetId)
        ) {
            throw new BadRequest();
        }

        $this->getOptOutService()->optOut($id, $targetType, $targetId);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws NotFound
     * @throws Forbidden
     */
    public function postActionCancelOptOut(Request $request): bool
    {
        $data = $request->getParsedBody();

        $id = $data->id ?? null;
        $targetType = $data->targetType ?? null;
        $targetId = $data->targetId ?? null;

        if (
            !is_string($id) ||
            !is_string($targetType) ||
            !is_string($targetId)
        ) {
            throw new BadRequest();
        }

        $this->getOptOutService()->cancelOptOut($id, $targetType, $targetId);

        return true;
    }

    private function getOptOutService(): OptOutService
    {
        return $this->injectableFactory->create(OptOutService::class);
    }
}
