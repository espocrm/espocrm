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

namespace Espo\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;

use Espo\Core\Utils\Config;
use Espo\Core\Authentication\TwoFactor\Email\Util;

use Espo\ORM\EntityManager;

use Espo\Entities\User;

class TwoFactorEmail
{
    private $util;

    private $user;

    private $entityManager;

    private $config;

    public function __construct(Util $util, User $user, EntityManager $entityManager, Config $config)
    {
        $this->util = $util;
        $this->user = $user;
        $this->entityManager = $entityManager;
        $this->config = $config;
    }

    public function sendCode(string $userId, string $emailAddress): void
    {
        if (!$this->user->isAdmin() && $userId !== $this->user->getId()) {
            throw new Forbidden();
        }

        $this->checkAllowed();

        $user = $this->entityManager->getEntity(User::ENTITY_TYPE, $userId);

        if (!$user) {
            throw new NotFound();
        }

        $this->util->sendCode($user, $emailAddress);
        $this->util->storeEmailAddress($user, $emailAddress);
    }

    private function checkAllowed(): void
    {
        if (!$this->config->get('auth2FA')) {
            throw new Forbidden("2FA is not enabled.");
        }

        $methodList = $this->config->get('auth2FAMethodList') ?? [];

        if (!in_array('Email', $methodList)) {
            throw new Forbidden("Email 2FA is not allowed.");
        }
    }
}
