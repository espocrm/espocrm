<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\Crm\EntryPoints;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\EntryPoint\Traits\NoAuth;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\HookManager;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Client\ActionRenderer;
use Espo\Entities\UniqueId;
use Espo\Modules\Crm\Entities\Meeting;

class EventConfirmation implements EntryPoint
{
    use NoAuth;

    private const ACTION_ACCEPT = 'accept';
    private const ACTION_DECLINE = 'decline';
    private const ACTION_TENTATIVE = 'tentative';

    public function __construct(
        private EntityManager $entityManager,
        private HookManager $hookManager,
        private ActionRenderer $actionRenderer
    ) {}

    /**
     * @throws BadRequest
     * @throws NotFound
     */
    public function run(Request $request, Response $response): void
    {
        $uid = $request->getQueryParam('uid') ?? null;
        $action = $request->getQueryParam('action') ?? null;

        if (!$uid) {
            throw new BadRequest();
        }

        if (!in_array($action, [self::ACTION_ACCEPT, self::ACTION_DECLINE, self::ACTION_TENTATIVE])) {
            throw new BadRequest();
        }

        /** @var ?UniqueId $uniqueId */
        $uniqueId = $this->entityManager
            ->getRDBRepositoryByClass(UniqueId::class)
            ->where(['name' => $uid])
            ->findOne();

        if (!$uniqueId) {
            throw new NotFound();
        }

        $data = $uniqueId->getData();

        $eventType = $data->eventType ?? null;
        $eventId = $data->eventId ?? null;
        $inviteeType = $data->inviteeType ?? null;
        $inviteeId = $data->inviteeId ?? null;
        $link = $data->link ?? null;

        $toProcess =
            $eventType &&
            $eventId &&
            $inviteeType &&
            $inviteeId &&
            $link;

        if (!$toProcess) {
            throw new BadRequest();
        }

        $event = $this->entityManager->getEntityById($eventType, $eventId);
        $invitee = $this->entityManager->getEntityById($inviteeType, $inviteeId);

        if (!$event || !$invitee) {
            throw new NotFound();
        }

        $eventStatus = $event->get('status');

        if (in_array($eventStatus, [Meeting::STATUS_HELD, Meeting::STATUS_NOT_HELD])) {
            throw new NotFound();
        }

        $status = match($action) {
            self::ACTION_ACCEPT => Meeting::ATTENDEE_STATUS_ACCEPTED,
            self::ACTION_DECLINE => Meeting::ATTENDEE_STATUS_DECLINED,
            self::ACTION_TENTATIVE => Meeting::ATTENDEE_STATUS_TENTATIVE,
            default => Meeting::ATTENDEE_STATUS_NONE,
        };

        $this->entityManager
            ->getRDBRepository($eventType)
            ->getRelation($event, $link)
            ->updateColumns($invitee, ['status' => $status]);

        $actionData = [
            'eventName' => $event->get('name'),
            'eventType' => $event->getEntityType(),
            'eventId' => $event->getId(),
            'dateStart' => $event->get('dateStart'),
            'action' => $action,
            'status' => $status,
            'link' => $link,
            'inviteeType' => $invitee->getEntityType(),
            'inviteeId' => $invitee->getId(),
        ];

        $this->hookManager->process(
            $event->getEntityType(),
            'afterConfirmation',
            $event,
            [],
            $actionData
        );

        $this->actionRenderer->write(
            $response,
            ActionRenderer\Params
                ::create('crm:controllers/event-confirmation', 'confirmEvent', $actionData)
        );
    }
}
