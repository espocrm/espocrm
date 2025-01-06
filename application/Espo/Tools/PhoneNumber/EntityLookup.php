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

namespace Espo\Tools\PhoneNumber;

use Espo\Entities\PhoneNumber;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Repositories\PhoneNumber as PhoneNumberRepository;

use RuntimeException;

/**
 * Entity lookup by a phone number.
 */
class EntityLookup
{
    private PhoneNumberRepository $internalRepository;

    public function __construct(
        private Repository $repository,
        EntityManager $entityManager
    ) {
        $repository = $entityManager->getRDBRepository(PhoneNumber::ENTITY_TYPE);

        if (!$repository instanceof PhoneNumberRepository) {
            throw new RuntimeException();
        }

        $this->internalRepository = $repository;
    }

    /**
     * Find entities by a phone number.
     *
     * @param string $number A phone number.
     * @return Entity[]
     */
    public function find(string $number): array
    {
        $phoneNumber = $this->repository->getByNumber($number);

        if (!$phoneNumber) {
            return [];
        }

        return $this->internalRepository->getEntityListByPhoneNumberId($phoneNumber->getId());
    }

    /**
     * Find a first entity by a phone number.
     *
     * @param string $number A phone number.
     * @param string[] $order An order entity type list.
     */
    public function findOne(string $number, ?array $order = null): ?Entity
    {
        $phoneNumber = $this->repository->getByNumber($number);

        if (!$phoneNumber) {
            return null;
        }

        if ($order) {
            $this->internalRepository->getEntityByPhoneNumberId($phoneNumber->getId(), null, $order);
        }

        return $this->internalRepository->getEntityByPhoneNumberId($phoneNumber->getId());
    }
}
