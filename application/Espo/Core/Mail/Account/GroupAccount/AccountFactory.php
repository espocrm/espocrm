<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\Mail\Account\GroupAccount;

use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Entities\InboundEmail;
use Espo\ORM\EntityManager;

class AccountFactory
{

    public function __construct(
        private InjectableFactory $injectableFactory,
        private EntityManager $entityManager
    ) {}

    /**
     * @throws Error
     */
    public function create(string $id): Account
    {
        $entity = $this->entityManager->getEntityById(InboundEmail::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new Error("InboundEmail '{$id}' not found.");
        }

        $binding = BindingContainerBuilder::create()
            ->bindInstance(InboundEmail::class, $entity)
            ->build();

        return $this->injectableFactory->createWithBinding(Account::class, $binding);
    }
}
