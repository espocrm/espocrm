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

namespace Espo\Core\Console\Commands;

use Espo\Entities\User;
use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\PasswordHash;

/**
 * @noinspection PhpUnused
 */
class SetPassword implements Command
{
    public function __construct(private EntityManager $entityManager, private PasswordHash $passwordHash)
    {}

    public function run(Params $params, IO $io): void
    {
        $userName = $params->getArgument(0);

        if (!$userName) {
            $io->writeLine("Username must be specified as the first argument.");
            $io->setExitStatus(1);

            return;
        }

        $em = $this->entityManager;

        $user = $em->getRDBRepositoryByClass(User::class)
            ->where(['userName' => $userName])
            ->findOne();

        if (!$user) {
            $io->writeLine("User '$userName' not found.");
            $io->setExitStatus(1);

            return;
        }

        $userType = $user->getType();

        $allowedTypes = [
            User::TYPE_ADMIN,
            User::TYPE_SUPER_ADMIN,
            User::TYPE_PORTAL,
            User::TYPE_REGULAR,
        ];

        if (!in_array($userType, $allowedTypes)) {
            $io->writeLine("Can't set password for a user of the type '$userType'.");
            $io->setExitStatus(1);

            return;
        }

        $io->writeLine("Enter a new password:");

        $password = $io->readSecretLine();

        if (!$password) {
            $io->writeLine("Password cannot be empty.");
            $io->setExitStatus(1);

            return;
        }

        $user->set('password', $this->passwordHash->hash($password));

        $em->saveEntity($user);

        $io->writeLine("Password for user '$userName' has been changed.");
    }
}
