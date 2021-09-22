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

namespace Espo\Modules\Crm\Classes\FieldProcessing\Meeting;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use Espo\Core\FieldProcessing\Saver;
use Espo\Core\FieldProcessing\Saver\Params;
use Espo\Core\Mail\Event\EventFactory;

use ICal\ICal;

class SourceEmailSaver implements Saver
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function process(Entity $entity, Params $params): void
    {
        if (!$entity->isNew()) {
            return;
        }

        $emailId = $entity->get('sourceEmailId');

        if (!$emailId) {
            return;
        }

        $email = $this->entityManager->getEntity('Email', $emailId);

        if (!$email) {
            return;
        }

        $icsContents = $email->get('icsContents');

        if ($icsContents === null) {
            return;
        }

        $ical = new ICal();

        $ical->initString($icsContents);

        $espoEvent = EventFactory::createFromU01jmg3Ical($ical);

        $email->set('createdEventId', $entity->getId());
        $email->set('createdEventType', $entity->getEntityType());
        $email->set('icsEventUid', $espoEvent->getUid());

        $this->entityManager->saveEntity($email);
    }
}
