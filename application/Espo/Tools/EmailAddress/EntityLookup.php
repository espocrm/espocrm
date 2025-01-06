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

namespace Espo\Tools\EmailAddress;

use Espo\Entities\EmailAddress;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Repositories\EmailAddress as EmailAddressRepository;

use RuntimeException;

/**
 * Entity lookup by an email address.
 */
class EntityLookup
{
    private EmailAddressRepository $internalRepository;

    public function __construct(
        private Repository $repository,
        EntityManager $entityManager
    ) {
        $repository = $entityManager->getRDBRepository(EmailAddress::ENTITY_TYPE);

        if (!$repository instanceof EmailAddressRepository) {
            throw new RuntimeException();
        }

        $this->internalRepository = $repository;
    }

    /**
     * Find entities by an email address.
     *
     * @param string $address An email address.
     * @return Entity[]
     */
    public function find(string $address): array
    {
        $emailAddress = $this->repository->getByAddress($address);

        if (!$emailAddress) {
            return [];
        }

        return $this->internalRepository->getEntityListByAddressId($emailAddress->getId());
    }

    /**
     * Find a first entity by an email address.
     *
     * @param string $address An email address.
     * @param string[] $order An order entity type list.
     */
    public function findOne(string $address, ?array $order = null): ?Entity
    {
        if ($order) {
            $this->internalRepository->getEntityByAddress($address, null, $order);
        }

        return $this->internalRepository->getEntityByAddress($address);
    }
}
