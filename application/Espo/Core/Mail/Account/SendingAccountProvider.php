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

namespace Espo\Core\Mail\Account;

use Espo\Core\Acl\Table;
use Espo\Core\AclManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\Mail\Account\GroupAccount\AccountFactory as GroupAccountFactory;
use Espo\Core\Mail\Account\PersonalAccount\AccountFactory as PersonalAccountFactory;
use Espo\Core\Mail\ConfigDataProvider;
use Espo\Core\Name\Field;
use Espo\Entities\EmailAccount as EmailAccountEntity;
use Espo\Entities\InboundEmail as InboundEmailEntity;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Part\Expression;
use RuntimeException;

class SendingAccountProvider
{
    private ?Account $system = null;
    private bool $systemIsCached = false;

    public function __construct(
        private EntityManager $entityManager,
        private GroupAccountFactory $groupAccountFactory,
        private PersonalAccountFactory $personalAccountFactory,
        private AclManager $aclManager,
        private ConfigDataProvider $configDataProvider,
    ) {}

    public function getShared(User $user, string $emailAddress): ?Account
    {
        $level = $this->aclManager->getPermissionLevel($user, 'groupEmailAccount');

        $entity = null;

        if ($level === Table::LEVEL_TEAM) {
            $teamIdList = $user->getTeamIdList();

            if ($teamIdList === []) {
                return null;
            }

            $entity = $this->entityManager
                ->getRDBRepositoryByClass(InboundEmailEntity::class)
                ->select([Attribute::ID])
                ->distinct()
                ->join(Field::TEAMS)
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
                ->select([Attribute::ID])
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
        } catch (Error) {
            throw new RuntimeException();
        }
    }

    public function getGroup(string $emailAddress): ?Account
    {
        $entity = $this->entityManager
            ->getRDBRepositoryByClass(InboundEmailEntity::class)
            ->select([Attribute::ID])
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
        } catch (Error) {
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
            ->select([Attribute::ID])
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
        } catch (Error) {
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
        $address = $this->configDataProvider->getSystemOutboundAddress();

        if (!$address) {
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
        } catch (Error) {
            throw new RuntimeException();
        }
    }
}
