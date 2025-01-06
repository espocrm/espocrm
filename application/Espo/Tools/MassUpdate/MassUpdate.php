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

namespace Espo\Tools\MassUpdate;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\Result;
use Espo\Core\MassAction\MassActionFactory;

use Espo\Core\Utils\SystemUser;
use Espo\ORM\EntityManager;
use Espo\Entities\User;

use RuntimeException;

/**
 * Entry point for the mass-update tool.
 */
class MassUpdate
{
    private const ACTION = 'massUpdate';

    public function __construct(
        private MassActionFactory $massActionFactory,
        private EntityManager $entityManager
    ) {}

    /**
     * Process.
     *
     * @param ?User $user Under what user to perform mass-update. If not specified, the system user will be used.
     * Access control is applied for the user.
     * @throws NotFound
     * @throws Forbidden
     * @throws BadRequest
     */
    public function process(Params $params, Data $data, ?User $user = null): Result
    {
        $entityType = $params->getEntityType();

        if (!$user) {
            $user = $this->entityManager
                ->getRDBRepositoryByClass(User::class)
                ->where(['userName' => SystemUser::NAME])
                ->findOne();
        }

        if (!$user) {
            throw new RuntimeException("No user.");
        }

        $action = $this->massActionFactory->createForUser(self::ACTION, $entityType, $user);

        return $action->process($params, $data->toMassActionData());
    }
}
