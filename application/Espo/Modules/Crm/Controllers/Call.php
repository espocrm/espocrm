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

use Espo\Core\Api\Response;
use Espo\Core\Controllers\Record;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Api\Request;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Utils\Json;
use Espo\Modules\Crm\Entities\Call as CallEntity;
use Espo\Modules\Crm\Tools\Meeting\InvitationService;
use Espo\Modules\Crm\Tools\Meeting\Invitation\Invitee;
use Espo\Modules\Crm\Tools\Meeting\Service;
use stdClass;

class Call extends Record
{
    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws SendingError
     * @throws NotFound
     */
    public function postActionSendInvitations(Request $request): bool
    {
        $id = $request->getParsedBody()->id ?? null;

        if (!$id) {
            throw new BadRequest();
        }

        $invitees = $this->fetchInvitees($request);

        $resultList = $this->injectableFactory
            ->create(InvitationService::class)
            ->send(CallEntity::ENTITY_TYPE, $id, $invitees);

        return $resultList !== 0;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws SendingError
     * @throws NotFound
     */
    public function postActionSendCancellation(Request $request): bool
    {
        $id = $request->getParsedBody()->id ?? null;

        if (!$id) {
            throw new BadRequest("No id.");
        }

        $invitees = $this->fetchInvitees($request);

        $resultList = $this->injectableFactory
            ->create(InvitationService::class)
            ->sendCancellation(CallEntity::ENTITY_TYPE, $id, $invitees);

        return $resultList !== 0;
    }

    /**
     * @param Request $request
     * @return ?\Espo\Modules\Crm\Tools\Meeting\Invitation\Invitee[]
     * @throws BadRequest
     */
    private function fetchInvitees(Request $request): ?array
    {
        $targets = $request->getParsedBody()->targets ?? null;

        if ($targets === null) {
            return null;
        }

        if (!is_array($targets)) {
            throw new BadRequest("No targets.");
        }

        $invitees = [];

        foreach ($targets as $target) {
            if (!$target instanceof stdClass) {
                throw new BadRequest("Bad target.");
            }

            $targetEntityType = $target->entityType ?? null;
            $targetId = $target->id ?? null;

            if (!is_string($targetEntityType) || !is_string($targetId)) {
                throw new BadRequest("No entityType or id.");
            }

            $invitees[] = new Invitee($targetEntityType, $targetId);
        }

        return $invitees;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function postActionMassSetHeld(Request $request): bool
    {
        $ids = $request->getParsedBody()->ids ?? null;

        if (!is_array($ids)) {
            throw new BadRequest("No `ids`.");
        }

        $this->injectableFactory
            ->create(Service::class)
            ->massSetHeld(CallEntity::ENTITY_TYPE, $ids);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function postActionMassSetNotHeld(Request $request): bool
    {
        $ids = $request->getParsedBody()->ids ?? null;

        if (!is_array($ids)) {
            throw new BadRequest("No `ids`.");
        }

        $this->injectableFactory
            ->create(Service::class)
            ->massSetNotHeld(CallEntity::ENTITY_TYPE, $ids);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws NotFound
     * @throws Forbidden
     */
    public function postActionSetAcceptanceStatus(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id) || empty($data->status)) {
            throw new BadRequest("No id or status.");
        }

        $this->injectableFactory
            ->create(Service::class)
            ->setAcceptance(CallEntity::ENTITY_TYPE, $data->id, $data->status);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function getActionAttendees(Request $request, Response $response): void
    {
        $id = $request->getRouteParam('id');

        if (!$id) {
            throw new BadRequest("No id.");
        }

        $collection = $this->injectableFactory
            ->create(Service::class)
            ->getAttendees(CallEntity::ENTITY_TYPE, $id);

        $response->writeBody(
            Json::encode(['list' => $collection->getValueMapList()])
        );
    }
}
