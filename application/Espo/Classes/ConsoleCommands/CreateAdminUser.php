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

namespace Espo\Classes\ConsoleCommands;

use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

use RuntimeException;

class CreateAdminUser implements Command
{
    public function __construct(
        private EntityManager $entityManager,
        private Config $config
    ) {}

    public function run(Params $params, IO $io): void
    {
        $userName = $params->getArgument(0);

        if (!$userName) {
            $io->writeLine("A username must be specified as the first argument.");
            $io->setExitStatus(1);

            return;
        }

        /** @var ?string $regExp */
        $regExp = $this->config->get('userNameRegularExpression');

        if (!$regExp) {
            throw new RuntimeException("No `userNameRegularExpression` in config.");
        }

        if (
            str_contains($userName, ' ') ||
            preg_replace("/{$regExp}/", '_', $userName) !== $userName
        ) {
            $io->writeLine("Not allowed username.");
            $io->setExitStatus(1);

            return;
        }

        $repository = $this->entityManager->getRDBRepositoryByClass(User::class);

        $existingUser = $repository
            ->where(['userName' => $userName])
            ->findOne();

        if ($existingUser) {
            $io->writeLine("A user with the same username already exists.");
            $io->setExitStatus(1);

            return;
        }

        $user = $repository->getNew();

        $user->set('userName', $userName);
        $user->set('type', User::TYPE_ADMIN);
        $user->set(Field::NAME, $userName);

        $repository->save($user);

        $message = "The user '{$userName}' has been created. " .
            "Set password with the command: `bin/command set-password {$userName}`.";

        $io->writeLine($message);
    }
}
