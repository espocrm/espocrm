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

declare(strict_types=1);

namespace Espo\Tools\EmailAccount\Api;

use Espo\Core\Acl;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Field\Date;
use Espo\Core\Record\EntityProvider;
use Espo\Core\Record\ServiceContainer;
use Espo\Entities\EmailAccount;
use Espo\Entities\InboundEmail;
use Espo\Tools\EmailAccount\ResetService;
use InvalidArgumentException;

/**
 * @noinspection PhpUnused
 */
class PostResetFetchData implements Action
{
    public function __construct(
        private EntityProvider $entityProvider,
        private Acl $acl,
        private ResetService $service,
        private ServiceContainer $serviceContainer,
    ) {}

    public function process(Request $request): Response
    {
        $entityType = $request->getRouteParam('entityType');
        $id = $request->getRouteParam('id') ?? throw new BadRequest();
        $fetchSinceRaw = $request->getParsedBody()->fetchSince ?? null;

        if (!is_string($fetchSinceRaw)) {
            throw new BadRequest("No or bad 'fetchSince'.");
        }

        try {
            $fetchSince = Date::fromString($fetchSinceRaw);
        } catch (InvalidArgumentException) {
            throw new BadRequest("Bad date.");
        }

        if ($entityType === EmailAccount::ENTITY_TYPE) {
            $entity = $this->entityProvider->getByClass(EmailAccount::class, $id);
        } else if ($entityType === InboundEmail::ENTITY_TYPE) {
            $entity = $this->entityProvider->getByClass(InboundEmail::class, $id);
        } else {
            throw new BadRequest();
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden("No edit access.");
        }

        $this->service->reset($entity, $fetchSince);

        $this->serviceContainer->get($entityType)->prepareEntityForOutput($entity);

        return ResponseComposer::json($entity->getValueMap());
    }
}
