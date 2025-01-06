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

namespace Espo\Core\Authentication\Oidc\UserProvider;

use Espo\Core\FieldProcessing\EmailAddress\Saver as EmailAddressSaver;
use Espo\Core\FieldProcessing\PhoneNumber\Saver as PhoneNumberSaver;
use Espo\Core\FieldProcessing\Relation\LinkMultipleSaver;
use Espo\Core\FieldProcessing\Saver\Params as SaverParams;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

class UserRepository
{
    public function __construct(
        private EntityManager $entityManager,
        private LinkMultipleSaver $linkMultipleSaver,
        private EmailAddressSaver $emailAddressSaver,
        private PhoneNumberSaver $phoneNumberSaver
    ) {}

    public function getNew(): User
    {
        return $this->entityManager->getRDBRepositoryByClass(User::class)->getNew();
    }

    public function save(User $user): void
    {
        $this->entityManager->saveEntity($user, [
            // Prevent `user` service being loaded by hooks.
            SaveOption::SKIP_HOOKS => true,
            SaveOption::KEEP_NEW => true,
            SaveOption::KEEP_DIRTY => true,
        ]);

        $saverParams = SaverParams::create()->withRawOptions(['skipLinkMultipleHooks' => true]);

        $this->linkMultipleSaver->process($user, Field::TEAMS, $saverParams);
        $this->linkMultipleSaver->process($user, 'portals', $saverParams);
        $this->linkMultipleSaver->process($user, 'portalRoles', $saverParams);
        $this->emailAddressSaver->process($user, $saverParams);
        $this->phoneNumberSaver->process($user, $saverParams);

        $user->setAsNotNew();
        $user->updateFetchedValues();

        $this->entityManager->refreshEntity($user);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where(['userName' => $username])
            ->findOne();
    }
}
