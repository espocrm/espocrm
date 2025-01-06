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

namespace Espo\Core\Authentication\TwoFactor\Totp;

use Espo\Core\Exceptions\BadRequest;
use Espo\Entities\UserData;
use Espo\Entities\User;
use Espo\Repositories\UserData as UserDataRepository;
use Espo\ORM\EntityManager;
use Espo\Core\Authentication\TwoFactor\UserSetup;
use Espo\Core\Utils\Config;

use RuntimeException;
use stdClass;

/**
 * @noinspection PhpUnused
 */
class TotpUserSetup implements UserSetup
{
    public function __construct(
        private Util $totp,
        private Config $config,
        private EntityManager $entityManager
    ) {}

    public function getData(User $user): stdClass
    {
        $userName = $user->get('userName');

        $secret = $this->totp->createSecret();

        $label = rawurlencode($this->config->get('applicationName')) . ':' . rawurlencode($userName);

        $this->storeSecret($user, $secret);

        return (object) [
            'auth2FATotpSecret' => $secret,
            'label' => $label,
        ];
    }

    public function verifyData(User $user, stdClass $payloadData): bool
    {
        $code = $payloadData->code ?? null;

        if ($code === null) {
            throw new BadRequest("No code.");
        }

        $codeModified = str_replace(' ', '', trim($code));

        if (!$codeModified) {
            return false;
        }

        $userData = $this->getUserDataRepository()->getByUserId($user->getId());

        if (!$userData) {
            throw new RuntimeException("User not found.");
        }

        $secret = $userData->get('auth2FATotpSecret');

        return $this->totp->verifyCode($secret, $codeModified);
    }

    private function storeSecret(User $user, string $secret): void
    {
        $userData = $this->getUserDataRepository()->getByUserId($user->getId());

        if (!$userData) {
            throw new RuntimeException();
        }

        $userData->set('auth2FATotpSecret', $secret);

        $this->entityManager->saveEntity($userData);
    }

    private function getUserDataRepository(): UserDataRepository
    {
        /** @var UserDataRepository $repository */
        $repository = $this->entityManager->getRepository(UserData::ENTITY_TYPE);

        return $repository;
    }
}
