<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Mail\Account;

use Espo\Core\Acl\Table;
use Espo\Core\AclManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\Mail\Account\GroupAccount\AccountFactory as GroupAccountFactory;
use Espo\Core\Mail\Account\PersonalAccount\AccountFactory as PersonalAccountFactory;
use Espo\Core\Utils\Config;
use Espo\Entities\EmailAccount as EmailAccountEntity;
use Espo\Entities\InboundEmail as InboundEmailEntity;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Part\Expression;
use RuntimeException;

class SendingAccountProvider
{
    private ?Account $system = null;
    private bool $systemIsCached = false;

    private EntityManager $entityManager;
    private Config $config;
    private GroupAccountFactory $groupAccountFactory;
    private PersonalAccountFactory $personalAccountFactory;
    private AclManager $aclManager;
    private SystemSettingsAccount $systemSettingsAccount;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        GroupAccountFactory $groupAccountFactory,
        PersonalAccountFactory $personalAccountFactory,
        AclManager $aclManager,
        SystemSettingsAccount $systemSettingsAccount
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->groupAccountFactory = $groupAccountFactory;
        $this->personalAccountFactory = $personalAccountFactory;
        $this->aclManager = $aclManager;
        $this->systemSettingsAccount = $systemSettingsAccount;
    }

    public function getShared(User $user, string $emailAddress): ?Account
    {
        $level = $this->aclManager->getPermissionLevel($user, 'groupEmailAccountPermission');

        $entity = null;

        if ($level === Table::LEVEL_TEAM) {
            $teamIdList = $user->getTeamIdList();

            if ($teamIdList === []) {
                return null;
            }

            $entity = $this->entityManager
                ->getRDBRepositoryByClass(InboundEmailEntity::class)
                ->select(['id'])
                ->distinct()
                ->join('teams')
                ->where([
                    'status' => InboundEmailEntity::STATUS_ACTIVE,
                    'useSmtp' => true,
                    'smtpIsShared' => true,
                    'teamsMiddle.teamId' => $teamIdList,
                ])
                ->where(
                    Condition::equal(
                        Expression::lowerCase(
                            Expression::column('emailAddress')
                        ),
                        strtolower($emailAddress)
                    )
                )
                ->findOne();
        }

        if ($level === Table::LEVEL_ALL) {
            $entity = $this->entityManager
                ->getRDBRepositoryByClass(InboundEmailEntity::class)
                ->select(['id'])
                ->where([
                    'status' => InboundEmailEntity::STATUS_ACTIVE,
                    'useSmtp' => true,
                    'smtpIsShared' => true,
                ])
                ->where(
                    Condition::equal(
                        Expression::lowerCase(
                            Expression::column('emailAddress')
                        ),
                        strtolower($emailAddress)
                    )
                )
                ->findOne();
        }

        if (!$entity) {
            return null;
        }

        try {
            return $this->groupAccountFactory->create($entity->getId());
        }
        catch (Error $e) {
            throw new RuntimeException();
        }
    }

    public function getGroup(string $emailAddress): ?Account
    {
        $entity = $this->entityManager
            ->getRDBRepositoryByClass(InboundEmailEntity::class)
            ->select(['id'])
            ->where([
                'status' => InboundEmailEntity::STATUS_ACTIVE,
                'useSmtp' => true,
                'smtpHost!=' => null,
            ])
            ->where(
                Condition::equal(
                    Expression::lowerCase(
                        Expression::column('emailAddress')
                    ),
                    strtolower($emailAddress)
                )
            )
            ->findOne();

        if (!$entity) {
            return null;
        }

        try {
            return $this->groupAccountFactory->create($entity->getId());
        }
        catch (Error $e) {
            throw new RuntimeException();
        }
    }

    /**
     * Get a personal user account.
     */
    public function getPersonal(User $user, ?string $emailAddress): ?Account
    {
        if (!$emailAddress) {
            $emailAddress = $user->getEmailAddress();
        }

        if (!$emailAddress) {
            return null;
        }

        $entity = $this->entityManager
            ->getRDBRepositoryByClass(EmailAccountEntity::class)
            ->select(['id'])
            ->where([
                'assignedUserId' => $user->getId(),
                'status' => EmailAccountEntity::STATUS_ACTIVE,
                'useSmtp' => true,
            ])
            ->where(
                Condition::equal(
                    Expression::lowerCase(
                        Expression::column('emailAddress')
                    ),
                    strtolower($emailAddress)
                )
            )
            ->findOne();

        if (!$entity) {
            return null;
        }

        try {
            return $this->personalAccountFactory->create($entity->getId());
        }
        catch (Error $e) {
            throw new RuntimeException();
        }
    }

    /**
     * Get a system account.
     */
    public function getSystem(): ?Account
    {
        if (!$this->systemIsCached) {
            $this->loadSystem();

            $this->systemIsCached = true;
        }

        return $this->system;
    }

    private function loadSystem(): void
    {
        $address = $this->config->get('outboundEmailFromAddress');

        if (!$address) {
            return;
        }

        if ($this->config->get('smtpServer')) {
            $this->system = $this->systemSettingsAccount;

            return;
        }

        $entity = $this->entityManager
            ->getRDBRepositoryByClass(InboundEmailEntity::class)
            ->where([
                'status' => InboundEmailEntity::STATUS_ACTIVE,
                'useSmtp' => true,
            ])
            ->where(
                Condition::equal(
                    Expression::lowerCase(
                        Expression::column('emailAddress')
                    ),
                    strtolower($address)
                )
            )
            ->findOne();

        if (!$entity) {
            return;
        }

        try {
            $this->system = $this->groupAccountFactory->create($entity->getId());
        }
        catch (Error $e) {
            throw new RuntimeException();
        }
    }
}
