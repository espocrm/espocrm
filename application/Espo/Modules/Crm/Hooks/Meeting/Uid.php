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

namespace Espo\Modules\Crm\Hooks\Meeting;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\Mail\Event\EventFactory;
use Espo\Core\Utils\Util;
use Espo\Entities\Email;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;
use ICal\ICal;

/**
 * @implements BeforeSave<Meeting>
 */
class Uid implements BeforeSave
{
    public function __construct(private EntityManager $entityManager) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isNew() || $entity->getUid()) {
            return;
        }

        $uid = $this->getUid($entity);

        $entity->setUid($uid);
    }

    private function getUid(Meeting $entity): string
    {
        $uid = $this->getIcsUid($entity);

        if ($uid) {
            return $uid;
        }

        return Util::generateUuid4();
    }

    private function getIcsUid(Meeting $entity): ?string
    {
        $email = $this->getEmail($entity);

        if (!$email) {
            return null;
        }

        $icsContents = $email->getIcsContents();

        if (!$icsContents) {
            return null;
        }

        $ical = new ICal();

        $ical->initString($icsContents);

        $espoEvent = EventFactory::createFromU01jmg3Ical($ical);

        return $espoEvent->getUid();
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
