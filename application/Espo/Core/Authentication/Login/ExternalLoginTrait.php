<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace Espo\Core\Authentication\Login;

use Espo\Core\{
    Authentication\AuthToken\AuthToken,
};

trait ExternalLoginTrait
{
    private $entityManager;

    /**
     * Login by authorization token.
     */
    protected function loginByToken($username, AuthToken $authToken = null)
    {
        if (!isset($authToken)) {
            return null;
        }

        $userId = $authToken->getUserId();

        $user = $this->entityManager->getEntity('User', $userId);
        if (!$user) {
            return;
        }

        $tokenUsername = $user->get('userName');

        if (strtolower($username) != strtolower($tokenUsername)) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            $this->log->alert(
                'Unauthorized access attempt for user [' . $username . '] from IP [' . $ip . ']'
            );

            return;
        }

        return $this->entityManager
            ->getRDBRepository('User')
            ->where([
                'userName' => $username,
            ])
            ->findOne();
    }

    /**
     * Create Espo user with external data
     */
    protected function createUser(array $userData)
    {
        $GLOBALS['log']->info('Creating new user ...');

        $user = $this->entityManager->getEntity('User');
        $user->set($userData);

        $this->entityManager->saveEntity($user, [
            // Prevent `user` service being loaded by hooks.
            'skipHooks' => true,
        ]);

        return $this->entityManager->getEntity('User', $user->id);
    }
}
