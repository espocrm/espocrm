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

namespace Espo\Modules\Crm\EntryPoints;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\EntryPoint\Traits\NoAuth;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\HookManager;
use Espo\Core\Name\Field;
use Espo\Core\ORM\EntityManager;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Client\ActionRenderer;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Note;
use Espo\Entities\UniqueId;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\ORM\Entity;

class EventConfirmation implements EntryPoint
{
    use NoAuth;

    private const NOTE_TYPE = 'EventConfirmation';

    private const ACTION_ACCEPT = 'accept';
    private const ACTION_DECLINE = 'decline';
    private const ACTION_TENTATIVE = 'tentative';

    public function __construct(
        private EntityManager $entityManager,
        private HookManager $hookManager,
        private ActionRenderer $actionRenderer,
        private Metadata $metadata,
        private Language $language
    ) {}

    /**
     * @throws BadRequest
     * @throws Error
     */
    public function run(Request $request, Response $response): void
    {
        $uid = $request->getQueryParam('uid') ?? null;
        $action = $request->getQueryParam('action') ?? null;

        if (!$uid) {
            throw new BadRequest("No uid.");
        }

        if (!in_array($action, [self::ACTION_ACCEPT, self::ACTION_DECLINE, self::ACTION_TENTATIVE])) {
            throw new BadRequest("Bad action.");
        }

        /** @var ?UniqueId $uniqueId */
        $uniqueId = $this->entityManager
            ->getRDBRepositoryByClass(UniqueId::class)
            ->where(['name' => $uid])
            ->findOne();

        if (!$uniqueId) {
            $this->actionRenderer->write($response, ActionRenderer\Params::create('controllers/base', 'error404'));

            return;
        }

        $data = $uniqueId->getData();

        $eventType = $data->eventType ?? null;
        $eventId = $data->eventId ?? null;
        $inviteeType = $data->inviteeType ?? null;
        $inviteeId = $data->inviteeId ?? null;
        $link = $data->link ?? null;
        $sentDateStart = $data->dateStart ?? null;

        $toProcess =
            $eventType &&
            $eventId &&
            $inviteeType &&
            $inviteeId &&
            $link;

        if (!$toProcess) {
            throw new Error("Bad data.");
        }

        if ($sentDateStart !== null && !is_string($sentDateStart)) {
            throw new Error("No date in data.");
        }

        $event = $this->entityManager->getEntityById($eventType, $eventId);
        $invitee = $this->entityManager->getEntityById($inviteeType, $inviteeId);

        if (!$event || !$invitee) {
            $this->actionRenderer->write($response, ActionRenderer\Params::create('controllers/base', 'error404'));

            return;
        }

        $isRelated =  $this->entityManager
            ->getRDBRepository($eventType)
            ->getRelation($event, $link)
            ->isRelated($invitee);

        $eventStatus = $event->get('status');

        if (!$isRelated) {
            $eventStatus = Meeting::STATUS_NOT_HELD;
        }

        if (in_array($eventStatus, [Meeting::STATUS_HELD, Meeting::STATUS_NOT_HELD])) {
            $actionData = [
                'eventName' => $event->get(Field::NAME),
                'translatedEntityType' => $this->language->translateLabel($eventType, 'scopeNames'),
                'translatedStatus' => $this->language->translateOption($eventStatus, 'status', $eventType),
                'style' => $this->metadata->get(['entityDefs', $eventType, 'fields', 'status', 'style', $eventStatus]),
            ];

            $this->actionRenderer->write(
                $response,
                ActionRenderer\Params
                    ::create('crm:controllers/event-confirmation', 'confirmEvent', $actionData)
            );

            return;
        }

        $status = match($action) {
            self::ACTION_ACCEPT => Meeting::ATTENDEE_STATUS_ACCEPTED,
            self::ACTION_DECLINE => Meeting::ATTENDEE_STATUS_DECLINED,
            self::ACTION_TENTATIVE => Meeting::ATTENDEE_STATUS_TENTATIVE,
            default => Meeting::ATTENDEE_STATUS_NONE,
        };

        $actionData = [
            'eventName' => $event->get(Field::NAME),
            'eventType' => $event->getEntityType(),
            'eventId' => $event->getId(),
            'dateStart' => $event->get('dateStart'),
            'action' => $action,
            'status' => $status,
            'link' => $link,
            'inviteeType' => $invitee->getEntityType(),
            'inviteeId' => $invitee->getId(),
        ];

        $currentStatus = $this->entityManager
            ->getRDBRepository($eventType)
            ->getRelation($event, $link)
            ->getColumn($invitee, 'status');

        if ($currentStatus !== $status) {
            $this->entityManager
                ->getRDBRepository($eventType)
                ->getRelation($event, $link)
                ->updateColumns($invitee, ['status' => $status]);

            if ($this->metadata->get(['scopes', $eventType, 'stream'])) {
                $this->createNote($event, $invitee, $status);
            }

            $this->hookManager->process(
                $event->getEntityType(),
                'afterConfirmation',
                $event,
                [],
                $actionData
            );
        }

        $actionData['translatedEntityType'] = $this->language->translateLabel($eventType, 'scopeNames');
        $actionData['translatedStatus'] = $this->language->translateOption($status, 'acceptanceStatus', $eventType);
        $actionData['style'] = $this->metadata
            ->get(['entityDefs', $eventType, 'fields', 'acceptanceStatus', 'style', $status]);
        $actionData['sentDateStart'] = $sentDateStart;
        $actionData['statusTranslation'] = $this->language->get([Meeting::ENTITY_TYPE, 'options', 'acceptanceStatus']);

        $this->actionRenderer->write(
            $response,
            ActionRenderer\Params
                ::create('crm:controllers/event-confirmation', 'confirmEvent', $actionData)
        );
    }

    private function createNote(Entity $entity, Entity $invitee, string $status): void
    {
        $options = $invitee->getEntityType() === User::ENTITY_TYPE ?
            [SaveOption::CREATED_BY_ID => $invitee->getId()] :
            [];

        $style = $this->metadata
            ->get(['entityDefs', $entity->getEntityType(), 'fields', 'acceptanceStatus', 'style', $status]);

        $this->entityManager->createEntity(Note::ENTITY_TYPE, [
            'type' => self::NOTE_TYPE,
            'parentId' => $entity->getId(),
            'parentType' => $entity->getEntityType(),
            'relatedId' => $invitee->getId(),
            'relatedType' => $invitee->getEntityType(),
            'data' => [
                'status' => $status,
                'style' => $style,
            ],
        ], $options);
    }
}
