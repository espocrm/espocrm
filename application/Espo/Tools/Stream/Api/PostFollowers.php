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

namespace Espo\Tools\Stream\Api;

use Espo\Core\Acl;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Tools\Stream\FollowerRecordService;

/**
 * @noinspection PhpUnused
 */
class PostFollowers implements Action
{
    public function __construct(
        private FollowerRecordService $service,
        private Acl $acl
    ) {}

    public function process(Request $request): Response
    {
        $entityType = $request->getRouteParam('entityType');
        $id = $request->getRouteParam('id');

        $data = $request->getParsedBody();

        if (!$entityType || !$id) {
            throw new BadRequest("No entityType or id.");
        }

        if (!$this->acl->check($entityType)) {
            throw new Forbidden("No access to $entityType.");
        }

        $ids = $data->ids ?? (isset($data->id) ? [$data->id] : []);

        if ($ids === [] || !is_array($ids)) {
            throw new BadRequest("No ids.");
        }

        foreach ($ids as $userId) {
            if (!is_string($userId)) {
                throw new BadRequest("Bad id item.");
            }
        }

        foreach ($ids as $userId) {
            $this->service->link($entityType, $id, $userId);
        }

        return ResponseComposer::json(true);
    }
}
