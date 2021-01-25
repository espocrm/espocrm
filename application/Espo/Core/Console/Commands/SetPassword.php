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

use Espo\Core\Container;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\PasswordHash;

class SetPassword implements Command
{
    protected $entityManager;

    public function __construct(EntityManager $entityManager, PasswordHash $passwordHash)
    {
        $this->entityManager = $entityManager;
        $this->passwordHash = $passwordHash;
    }

    public function run(array $options, array $flagList, array $argumentList)
    {
        $userName = $argumentList[0] ?? null;

        if (!$userName) {
            $this->out("User name must be specified.\n");
            die;
        }

        $em = $this->entityManager;

        $user = $em->getRepository('User')->where(['userName' => $userName])->findOne();

        if (!$user) {
            $this->out("User '{$userName}' not found.\n");
            die;
        }

        if (!in_array($user->get('type'), ['admin', 'super-admin', 'portal', 'regular'])) {
            $this->out("Can't set password for user of type '".$user->get('type')."'.\n");
            die;
        }

        $this->out("Enter a new password:\n");

        $password = $this->ask();

        $password = trim($password);

        if (!$password) {
            $this->out("Password can not be empty.\n");
            die;
        }

        $hash = $this->passwordHash;

        $user->set('password', $hash->hash($password));

        $em->saveEntity($user);

        $this->out("Password for user '{$userName}' is changed.\n");
    }

    protected function ask()
    {
        $input = fgets(\STDIN);

        return rtrim($input, "\n");
    }

    protected function out($string)
    {
        fwrite(\STDOUT, $string);
    }
}
