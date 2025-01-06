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

namespace Espo\Modules\Crm\Classes\FieldProcessing\Meeting;

use Espo\Entities\Email;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Core\FieldProcessing\Saver;
use Espo\Core\FieldProcessing\Saver\Params;
use Espo\Core\Mail\Event\EventFactory;

use ICal\ICal;

/**
 * @implements Saver<Meeting>
 */
class SourceEmailSaver implements Saver
{
    public function __construct(private EntityManager $entityManager)
    {}

    /**
     * @param Meeting $entity
     */
    public function process(Entity $entity, Params $params): void
    {
        if (!$entity->isNew()) {
            return;
        }

        $email = $this->getEmail($entity);

        if (!$email) {
            return;
        }

        $icsContents = $email->getIcsContents();

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

    private function getEmail(Meeting $entity): ?Email
    {
        $emailId = $entity->get('sourceEmailId');

        if (!$emailId) {
            return null;
        }

        return $this->entityManager->getRDBRepositoryByClass(Email::class)->getById($emailId);
    }
}
