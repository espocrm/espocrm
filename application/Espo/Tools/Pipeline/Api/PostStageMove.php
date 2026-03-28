<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Tools\Pipeline\Api;

use Espo\Core\Acl;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\EntityProvider;
use Espo\Entities\PipelineStage;
use Espo\Tools\Pipeline\MoveService;
use Espo\Tools\Pipeline\StageMoveService;

/**
 * @noinspection PhpUnused
 */
class PostStageMove implements Action
{
    public function __construct(
        private EntityProvider $entityProvider,
        private Acl $acl,
        private StageMoveService $moveService,
    ) {}

    public function process(Request $request): Response
    {
        $entity = $this->getEntity($request);
        $type = $this->fetchType($request);

        if ($type === MoveService::TYPE_UP) {
            $this->moveService->moveUp($entity);
        } else {
            $this->moveService->moveDown($entity);
        }

        return ResponseComposer::json(true);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    private function getEntity(Request $request): PipelineStage
    {
        $id = $request->getRouteParam('id') ?? throw new BadRequest();

        if (!$this->acl->checkScope(PipelineStage::ENTITY_TYPE)) {
            throw new Forbidden();
        }

        $entity = $this->entityProvider->getByClass(PipelineStage::class, $id);

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden();
        }

        return $entity;
    }

    /**
     * @return MoveService::TYPE_UP|MoveService::TYPE_DOWN
     * @throws BadRequest
     */
    private function fetchType(Request $request): string
    {
        $type = $request->getRouteParam('type') ?? throw new BadRequest();

        if (
            !in_array($type, [
                MoveService::TYPE_UP,
                MoveService::TYPE_DOWN,
            ])
        ) {
            throw new BadRequest("Not supported type.");
        }

        return $type;
    }
}
