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

namespace Espo\Classes\FieldProcessing\Email;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Entities\EmailAddress;
use Espo\Entities\Email;

use Espo\Core\{
    FieldProcessing\Loader,
    FieldProcessing\Loader\Params,
    Mail\Event\Event as EspoEvent,
    Mail\Event\EventFactory,
    Utils\Log,
};

use ICal\ICal;
use ICal\Event;

use Throwable;

class IcsDataLoader implements Loader
{
    private $entityManager;

    private $log;

    private $entityTypeLinkMap = [
        'User' => 'users',
        'Contact' => 'contacts',
        'Lead' => 'leads',
    ];

    public function __construct(EntityManager $entityManager, Log $log)
    {
        $this->entityManager = $entityManager;
        $this->log = $log;
    }

    public function process(Entity $entity, Params $params): void
    {
        $icsContents = $entity->get('icsContents');

        if ($icsContents === null) {
            return;
        }

        $ical = new ICal();

        $ical->initString($icsContents);

        /* @var $event Event */
        $event = $ical->events()[0] ?? null;

        if ($event === null) {
            return;
        }

        if ($event->status === 'CANCELLED') {
            return;
        }

        $espoEvent = EventFactory::createFromU01jmg3Ical($ical);

        $valueMap = (object) [
            'sourceEmailId' => $entity->getId(),
        ];

        try {
            $valueMap->name = $espoEvent->getName();
            $valueMap->description = $espoEvent->getDescription();
            $valueMap->dateStart = $espoEvent->getDateStart();
            $valueMap->dateEnd = $espoEvent->getDateEnd();
            $valueMap->location = $espoEvent->getLocation();
            $valueMap->isAllDay = $espoEvent->isAllDay();

            if ($espoEvent->isAllDay()) {
                $valueMap->dateStartDate = $espoEvent->getDateStart();
                $valueMap->dateEndDate = $espoEvent->getDateEnd();
            }
        }
        catch (Throwable $e) {
            $this->log->warning("Error while converting ICS event '" . $entity->getId() . "': " . $e->getMessage());

            return;
        }

        /** @var EmailAddressRepository $emailAddressRepository */
        $emailAddressRepository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

        $attendeeEmailAddressList = $espoEvent->getAttendeeEmailAddressList();

        $organizerEmailAddress = $espoEvent->getOrganizerEmailAddress();

        if ($organizerEmailAddress) {
            $attendeeEmailAddressList[] = $organizerEmailAddress;
        }

        foreach ($attendeeEmailAddressList as $address) {
            $personEntity = $emailAddressRepository->getEntityByAddress($address);

            if (!$personEntity) {
                continue;
            }

            $link = $this->entityTypeLinkMap[$personEntity->getEntityType()] ?? null;

            if (!$link) {
                continue;
            }

            $idsAttribute = $link . 'Ids';
            $namesAttribute = $link . 'Names';

            $idList = $valueMap->$idsAttribute ?? [];
            $nameMap = $valueMap->$namesAttribute ?? (object) [];

            $idList[] = $personEntity->getId();
            $nameMap->{$personEntity->getId()} = $personEntity->get('name');

            $valueMap->$idsAttribute = $idList;
            $valueMap->$namesAttribute = $nameMap;
        }

        $eventData = (object) [
            'valueMap' => $valueMap,
            'uid' => $espoEvent->getUid(),
            'createdEvent' => null,
        ];

        $this->loadCreatedEvent($entity, $espoEvent, $eventData);

        $entity->set('icsEventData', $eventData);

        $entity->set('icsEventDateStart', $espoEvent->getDateStart());

        if ($espoEvent->isAllDay()) {
            $entity->set('icsEventDateStartDate', $espoEvent->getDateStart());
        }
    }

    private function loadCreatedEvent(Entity $entity, EspoEvent $espoEvent, object $eventData): void
    {
        $emailSameEvent = $this->entityManager
            ->getRDBRepository(Email::ENTITY_TYPE)
            ->where([
                'icsEventUid' => $espoEvent->getUid(),
                'id!=' => $entity->getId()
            ])
            ->findOne();

        if (!$emailSameEvent) {
            return;
        }

        if (
            !$emailSameEvent->get('createdEventId') ||
            !$emailSameEvent->get('createdEventType')
        ) {
            return;
        }

        $createdEvent = $this->entityManager
            ->getEntity($emailSameEvent->get('createdEventType'), $emailSameEvent->get('createdEventId'));

        if (!$createdEvent) {
            return;
        }

        $eventData->createdEvent = (object) [
            'id' => $createdEvent->getId(),
            'entityType' => $emailSameEvent->getEntityType(),
            'name' => $createdEvent->get('name'),
        ];
    }
}
