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
use Espo\ORM\EntityManager;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use RuntimeException;

/**
 * An email address repository.
 */
class Repository
{
    private EmailAddressRepository $repository;

    public function __construct(EntityManager $entityManager)
    {
        $repository = $entityManager->getRDBRepository(EmailAddress::ENTITY_TYPE);

        if (!$repository instanceof EmailAddressRepository) {
            throw new RuntimeException();
        }

        $this->repository = $repository;
    }

    /**
     * Get an email address entity by a string address.
     */
    public function getByAddress(string $address): ?EmailAddress
    {
        return $this->repository->getByAddress($address);
    }
}
