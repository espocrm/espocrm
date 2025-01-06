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

namespace Espo\Hooks\Sms;

use Espo\ORM\EntityManager;
use Espo\Entities\Sms;
use Espo\Entities\PhoneNumber;
use Espo\Repositories\PhoneNumber as PhoneNumberRepository;

use Espo\ORM\Entity;

class Numbers
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function beforeSave(Entity $entity): void
    {
        assert($entity instanceof Sms);

        $this->processNumbers($entity);
    }

    private function processNumbers(Sms $entity): void
    {
        if ($entity->has('from')) {
            $this->processFrom($entity);
        }

        if ($entity->has('to')) {
            $this->processTo($entity);
        }
    }

    private function processFrom(Sms $entity): void
    {
        $from = $entity->get('from');

        $entity->set('fromPhoneNumberId', null);
        $entity->set('fromEmailAddressName', null);

        if (!$from) {
            return;
        }

        $numberIds = $this->getPhoneNumberRepository()->getIds([$from]);

        if (!count($numberIds)) {
            return;
        }

        $entity->set('fromEmailAddressId', $numberIds[0]);
        $entity->set('fromEmailAddressName', $from);
    }

    private function processTo(Sms $entity): void
    {
        $entity->setLinkMultipleIdList('toPhoneNumbers', []);

        $to = $entity->get('to');

        if ($to === null || !$to) {
            return;
        }

        $numberList = array_map(
            function (string $item): string {
                return trim($item);
            },
            explode(';', $to)
        );

        $numberIds = $this->getPhoneNumberRepository()->getIds($numberList);

        $entity->setLinkMultipleIdList('toPhoneNumbers', $numberIds);
    }

    private function getPhoneNumberRepository(): PhoneNumberRepository
    {
        /** @var PhoneNumberRepository */
        return $this->entityManager->getRepository(PhoneNumber::ENTITY_TYPE);
    }
}
