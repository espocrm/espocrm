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

namespace Espo\Core\Console\Commands;

use Espo\Core\{
    ORM\EntityManager,
    Authentication\AuthToken\Manager as AuthTokenManager,
    Console\Command,
    Console\Command\Params,
    Console\IO,
};

class AuthTokenCheck implements Command
{
    private $entityManager;

    private $authTokenManager;

    public function __construct(EntityManager $entityManager, AuthTokenManager $authTokenManager)
    {
        $this->entityManager = $entityManager;
        $this->authTokenManager = $authTokenManager;
    }

    public function run(Params $params, IO $io): void
    {
        $token = $params->getArgument(0);

        if (empty($token)) {
            return;
        }

        $authToken = $this->authTokenManager->get($token);

        if (!$authToken) {
            return;
        }

        if (!$authToken->isActive()) {
            return;
        }

        if (!$authToken->getUserId()) {
            return;
        }

        $userId = $authToken->getUserId();

        $user = $this->entityManager
            ->getRDBRepository('User')
            ->select('id')
            ->where([
                'id' => $userId,
                'isActive' => true,
            ])
            ->findOne();

        if (!$user) {
            return;
        }

        $io->write($user->getId());
    }
}
