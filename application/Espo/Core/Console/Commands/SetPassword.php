<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class SetPassword extends Base
{
    public function run($options, $flagList, $argumentList)
    {
        $userName = $argumentList[0] ?? null;

        if (!$userName) {
            $this->out("User name must be specified.\n");
            die;
        }

        $em = $this->getContainer()->get('entityManager');

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

        if (!$password) {
            $this->out("Password can not be empty.\n");
            die;
        }

        $hash = $this->getContainer()->get('passwordHash');

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
