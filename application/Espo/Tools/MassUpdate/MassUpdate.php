<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\MassUpdate;

use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\Result;
use Espo\Core\MassAction\MassActionFactory;

use Espo\ORM\EntityManager;
use Espo\Entities\User;

use RuntimeException;

/**
 * Entry point for the mass-update tool.
 */
class MassUpdate
{
    private MassActionFactory $massActionFactory;

    private EntityManager $entityManager;

    private const ACTION = 'massUpdate';

    private const DEFAULT_USER_ID = 'system';

    public function __construct(MassActionFactory $massActionFactory, EntityManager $entityManager)
    {
        $this->massActionFactory = $massActionFactory;
        $this->entityManager = $entityManager;
    }

    /**
     * @param ?User $user Under what user to perform mass-update. If not specified, the system user will be used.
     *   Access control is applied for the user.
     * @throws \Espo\Core\Exceptions\NotFound
     */
    public function process(Params $params, Data $data, ?User $user = null): Result
    {
        $entityType = $params->getEntityType();

        if (!$user) {
            $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, self::DEFAULT_USER_ID);
        }

        if (!$user) {
            throw new RuntimeException("No user.");
        }

        $action = $this->massActionFactory->createForUser(self::ACTION, $entityType, $user);

        return $action->process($params, $data->toMassActionData());
    }
}
