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
    Utils\PasswordHash,
    Console\Command,
    Console\Command\Params,
    Console\IO,
};

class SetPassword implements Command
{
    private $entityManager;

    private $passwordHash;

    public function __construct(EntityManager $entityManager, PasswordHash $passwordHash)
    {
        $this->entityManager = $entityManager;
        $this->passwordHash = $passwordHash;
    }

    public function run(Params $params, IO $io): void
    {
        $userName = $params->getArgument(0);

        if (!$userName) {
            $io->writeLine("User name must be specified.");

            return;
        }

        $em = $this->entityManager;

        $user = $em->getRDBRepository('User')
            ->where(['userName' => $userName])
            ->findOne();

        if (!$user) {
            $io->writeLine("User '{$userName}' not found.");

            return;
        }

        if (!in_array($user->get('type'), ['admin', 'super-admin', 'portal', 'regular'])) {
            $userType = $user->get('type');

            $io->writeLine(
                "Can't set password for a user of the type '{$userType}'."
            );

            return;
        }

        $io->writeLine("Enter a new password:");

        $password = $io->readLine();

        if (!$password) {
            $io->writeLine("Password can not be empty.");

            return;
        }

        $hash = $this->passwordHash;

        $user->set('password', $hash->hash($password));

        $em->saveEntity($user);

        $io->writeLine("Password for user '{$userName}' is changed.");
    }
}
