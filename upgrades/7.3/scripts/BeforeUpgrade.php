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

use Espo\Core\Container;
use Espo\Entities\EmailAccount;
use Espo\Entities\User;
use Espo\Entities\Preferences;
use Espo\ORM\EntityManager;

class BeforeUpgrade
{
    public function run(Container $container): void
    {
        $this->migrateSmtp($container->get('entityManager'));
    }

    private function migrateSmtp(EntityManager $entityManager): void
    {
        $users = $entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where([
                'isActive' => true,
                'type' => [
                    User::TYPE_REGULAR,
                    User::TYPE_ADMIN,
                ]
            ])
            ->find();

        foreach ($users as $user) {
            $this->migrateSmtpForUser($entityManager, $user);
        }
    }

    private function migrateSmtpForUser(EntityManager $entityManager, User $user): void
    {
        $preferences = $entityManager->getEntityById(Preferences::ENTITY_TYPE, $user->getId());

        if (!$preferences) {
            return;
        }

        $emailAddress = $user->getEmailAddress();
        $smtpServer = $preferences->get('smtpServer');

        if (!$smtpServer) {
            return;
        }

        if (!$emailAddress) {
            return;
        }

        $existingAccount = $entityManager
            ->getRDBRepositoryByClass(EmailAccount::class)
            ->where([
                'assignedUserId' => $user->getId(),
                'emailAddress' => $emailAddress,
                'useSmtp' => true,
                'status' => 'Active',
            ])
            ->findOne();

        if ($existingAccount) {
            return;
        }

        $account = $entityManager
            ->getRDBRepositoryByClass(EmailAccount::class)
            ->getNew();

        $account->set([
            'assignedUserId' => $user->getId(),
            'name' => $emailAddress . ' (auto-created)',
            'emailAddress' => $emailAddress,
            'useSmtp' => true,
            'status' => 'Active',
            'smtpHost' => $smtpServer,
            'smtpPort' => $preferences->get('smtpPort'),
            'smtpAuth' => $preferences->get('smtpAuth'),
            'smtpSecurity' => $preferences->get('smtpSecurity'),
            'smtpUsername' => $preferences->get('smtpUsername'),
            'smtpPassword' => $preferences->get('smtpPassword'),
        ]);

        $entityManager->saveEntity($account);

        $preferences->set('smtpServer', null);
        $preferences->set('smtpPort', null);
        $preferences->set('smtpAuth', null);
        $preferences->set('smtpAuth', null);
        $preferences->set('smtpSecurity', null);
        $preferences->set('smtpUsername', null);
        $preferences->set('smtpPassword', null);

        $entityManager->saveEntity($preferences);
    }
}
